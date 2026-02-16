<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Exceptions\ConfigurationException;
use Denprog\Meridian\Exceptions\GeoIPUpdaterException;
use Denprog\Meridian\Services\Drivers\GeoIP\MaxMindDatabaseDriver;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PharData;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

#[AsCommand(name: 'meridian:update-geoip-db')]
class UpdateGeoipDbCommand extends Command
{
    private const string LOCK_KEY = 'meridian:update-geoip-db';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:update-geoip-db
        {--dry-run : Show execution plan without downloading files}
        {--retries= : Number of retry attempts for archive download}
        {--retry-delay= : Delay in milliseconds between retries}
        {--lock-seconds= : Cache lock lifetime in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads or updates the MaxMind GeoIP2 database(s).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting GeoIP database update process...');

        try {
            $retries = $this->resolvePositiveIntOption(
                'retries',
                $this->readIntConfig('meridian.commands.update_geoip_db.retries', 3)
            );
            $retryDelayMs = $this->resolveNonNegativeIntOption(
                'retry-delay',
                $this->readIntConfig('meridian.commands.update_geoip_db.retry_delay_ms', 1000)
            );
            $lockSeconds = $this->resolvePositiveIntOption(
                'lock-seconds',
                $this->readIntConfig('meridian.commands.update_geoip_db.lock_seconds', 1800)
            );

            $url = 'https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz';

            if ((bool) $this->option('dry-run')) {
                $previewRelativePath = $this->readStringConfig(
                    'meridian.geolocation.drivers.maxmind_database.database_path',
                    'meridian'
                );
                $previewExpectedSha256 = $this->readStringConfig(
                    'meridian.geolocation.drivers.maxmind_database.expected_sha256',
                    ''
                );

                $absoluteStorageDirectory = storage_path($previewRelativePath === '' ? 'meridian' : $previewRelativePath);

                $this->warn('Dry run mode enabled. No files were downloaded or replaced.');
                $this->line("  URL: $url");
                $this->line("  Target directory: $absoluteStorageDirectory");
                $this->line("  Retry attempts: $retries");
                $this->line("  Retry delay: {$retryDelayMs}ms");
                if ($previewExpectedSha256 !== '') {
                    $this->line('  SHA-256 validation: enabled');
                }

                return self::SUCCESS;
            }

            $lock = Cache::lock(self::LOCK_KEY, $lockSeconds);
            if (! $lock->get()) {
                $this->warn('Another GeoIP update is already running. Skipping.');

                return self::SUCCESS;
            }

            try {
                $licenseKey = $this->readStringConfig('meridian.geolocation.drivers.maxmind_database.license_key');
                $accountId = $this->readStringConfig('meridian.geolocation.drivers.maxmind_database.account_id');
                $relativeDbPath = $this->readStringConfig('meridian.geolocation.drivers.maxmind_database.database_path');
                $expectedSha256 = $this->readStringConfig('meridian.geolocation.drivers.maxmind_database.expected_sha256', '');
                $minimumArchiveBytes = $this->readIntConfig('meridian.commands.update_geoip_db.min_archive_bytes', 10_240);

                if ($licenseKey === '') {
                    throw new ConfigurationException('MaxMind license key is not configured (meridian.geolocation.drivers.maxmind_database.license_key).');
                }
                if ($accountId === '') {
                    throw new ConfigurationException('MaxMind account id is not configured (meridian.geolocation.drivers.maxmind_database.account_id).');
                }
                if ($relativeDbPath === '') {
                    throw new ConfigurationException('MaxMind database storage path is not configured (meridian.geolocation.drivers.maxmind_database.database_path).');
                }

                $absoluteStorageDirectory = storage_path($relativeDbPath);

                if (! is_dir($absoluteStorageDirectory)) {
                    File::ensureDirectoryExists($absoluteStorageDirectory);
                    $this->line("Created storage directory: $absoluteStorageDirectory");
                }
                if (! is_writable($absoluteStorageDirectory)) {
                    throw new GeoIPUpdaterException("GeoIP database storage directory is not writable: $absoluteStorageDirectory");
                }

                $response = $this->downloadArchiveWithRetry($url, $accountId, $licenseKey, $retries, $retryDelayMs);

                $contentDisposition = $response->header('Content-Disposition');
                $filename = 'geoip_download.tar.gz';

                if ($contentDisposition && preg_match('/filename="?([^"]+)"?/', $contentDisposition, $matches)) {
                    $filename = $matches[1];
                }

                $archivePath = $this->persistDownloadedArchive(
                    $absoluteStorageDirectory,
                    $filename,
                    $response,
                    $expectedSha256,
                    $minimumArchiveBytes
                );
                $this->processGeoLiteArchive($archivePath, $absoluteStorageDirectory);
            } finally {
                $lock->release();
            }

            $this->info('GeoIP database update process finished successfully.');

            return self::SUCCESS;

        } catch (RuntimeException $e) {
            $this->error('Input error: '.$e->getMessage());

            return CommandAlias::FAILURE;
        } catch (ConfigurationException $e) {
            $this->error('Configuration error: '.$e->getMessage());
            Log::error('GeoIP DB Update Configuration Error: '.$e->getMessage());

            return self::FAILURE;
        } catch (GeoIPUpdaterException $e) {
            $this->error('GeoIP Updater error: '.$e->getMessage());
            Log::error('GeoIP DB Updater Error: '.$e->getMessage());

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error('An unexpected error occurred: '.$e->getMessage());
            Log::error('GeoIP DB Update Unexpected Error: '.$e->getMessage(), ['exception' => $e]);

            return self::FAILURE;
        }
    }

    private function readStringConfig(string $key, string $default = ''): string
    {
        $value = config()->get($key, $default);

        return is_string($value) ? $value : $default;
    }

    private function readIntConfig(string $key, int $default): int
    {
        $value = config()->get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * @throws GeoIPUpdaterException
     */
    private function downloadArchiveWithRetry(
        string $url,
        string $accountId,
        string $licenseKey,
        int $retries,
        int $retryDelayMs
    ): Response {
        $lastThrowable = null;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                /** @var Response $response */
                $response = Http::withBasicAuth($accountId, $licenseKey)
                    ->timeout(300)
                    ->accept('application/gzip')
                    ->get($url);

                if ($response->successful()) {
                    return $response;
                }

                $lastThrowable = new GeoIPUpdaterException(
                    "Failed to download GeoIP database: {$response->status()} {$response->body()}"
                );
            } catch (Throwable $throwable) {
                $lastThrowable = $throwable;
            }

            if ($attempt < $retries) {
                $this->warn("Download attempt $attempt/$retries failed. Retrying...");
                if ($retryDelayMs > 0) {
                    usleep($retryDelayMs * 1000);
                }
            }
        }

        throw new GeoIPUpdaterException(
            'Failed to download GeoIP database after all retry attempts.',
            previous: $lastThrowable instanceof Throwable ? $lastThrowable : null
        );
    }

    /**
     * @throws GeoIPUpdaterException
     */
    private function persistDownloadedArchive(
        string $absoluteStorageDirectory,
        string $filename,
        Response $response,
        string $expectedSha256,
        int $minimumArchiveBytes
    ): string {
        $this->assertValidArchiveResponse($response, $minimumArchiveBytes);

        $body = $response->body();
        if ($expectedSha256 !== '') {
            $actualHash = hash('sha256', $body);
            if (! hash_equals(mb_strtolower($expectedSha256), mb_strtolower($actualHash))) {
                throw new GeoIPUpdaterException(
                    "Downloaded GeoIP archive checksum mismatch. Expected: $expectedSha256, got: $actualHash."
                );
            }
        }

        $temporaryPath = $absoluteStorageDirectory.DIRECTORY_SEPARATOR.$filename.'.download';
        $finalPath = $absoluteStorageDirectory.DIRECTORY_SEPARATOR.$filename;

        if (File::put($temporaryPath, $body) === false) {
            throw new GeoIPUpdaterException("Failed to write downloaded archive to $temporaryPath");
        }

        if (File::exists($finalPath)) {
            File::delete($finalPath);
        }

        if (! File::move($temporaryPath, $finalPath)) {
            File::delete($temporaryPath);
            throw new GeoIPUpdaterException("Failed to move downloaded archive to $finalPath");
        }

        return $finalPath;
    }

    /**
     * @throws GeoIPUpdaterException
     */
    private function assertValidArchiveResponse(Response $response, int $minimumArchiveBytes): void
    {
        $bodyLength = mb_strlen($response->body(), '8bit');
        if ($bodyLength < $minimumArchiveBytes) {
            throw new GeoIPUpdaterException(
                "Downloaded GeoIP archive is unexpectedly small ($bodyLength bytes)."
            );
        }

        $contentType = mb_strtolower((string) $response->header('Content-Type'));
        if ($contentType === '') {
            return;
        }

        $validTypes = ['application/gzip', 'application/x-gzip', 'application/octet-stream'];
        foreach ($validTypes as $validType) {
            if (str_contains($contentType, $validType)) {
                return;
            }
        }

        throw new GeoIPUpdaterException("Unexpected GeoIP archive content type: $contentType");
    }

    private function resolvePositiveIntOption(string $name, int $defaultValue): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return max(1, $defaultValue);
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("Option --$name must be a positive integer.");
        }

        $resolved = (int) $value;
        if ($resolved < 1) {
            throw new RuntimeException("Option --$name must be a positive integer.");
        }

        return $resolved;
    }

    private function resolveNonNegativeIntOption(string $name, int $defaultValue): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return max(0, $defaultValue);
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("Option --$name must be a non-negative integer.");
        }

        $resolved = (int) $value;
        if ($resolved < 0) {
            throw new RuntimeException("Option --$name must be a non-negative integer.");
        }

        return $resolved;
    }

    /**
     * Extract a GeoLite2 archive (.tar.gz), find the .mmdb file and move it
     * into the target directory.
     *
     * @param  string  $archivePath  Absolute path to downloaded .tar.gz archive.
     * @param  string  $targetDirectory  Absolute target directory for the .mmdb file.
     *
     * @throws Exception
     */
    private function processGeoLiteArchive(
        string $archivePath,
        string $targetDirectory,
    ): void {
        $tempExtractPath = storage_path('app/geoip_temp_extract_'.uniqid());
        $fileName = config()->string('meridian.geolocation.drivers.maxmind_database.database_filename', MaxMindDatabaseDriver::FILE_NAME);

        try {
            if (! File::exists($archivePath)) {
                throw new Exception("Archive file not found: $archivePath");
            }

            File::ensureDirectoryExists($tempExtractPath);

            $phar = new PharData($archivePath);
            $phar->decompress();

            $tarPath = str_replace('.tar.gz', '.tar', $archivePath);
            if (! File::exists($tarPath)) {
                $tarPath = $archivePath;
            }

            $pharTar = new PharData($tarPath);
            $pharTar->extractTo($tempExtractPath, null, true);

            $this->info("Archive $archivePath extracted to $tempExtractPath");

            $foundMmdbFile = null;
            $filesAndFolders = File::directories($tempExtractPath);

            if (! empty($filesAndFolders)) {
                $potentialMmdbDir = $filesAndFolders[0];
                if (is_string($potentialMmdbDir)) {
                    $expectedMmdbPathInArchive = $potentialMmdbDir.'/'.$fileName;

                    if (File::exists($expectedMmdbPathInArchive)) {
                        $foundMmdbFile = $expectedMmdbPathInArchive;
                    }
                }
            }

            if ($foundMmdbFile === null) {
                $allFiles = File::allFiles($tempExtractPath);
                foreach ($allFiles as $file) {
                    if ($file->getFilename() === $fileName) {
                        $foundMmdbFile = $file->getRealPath();
                        break;
                    }
                }
            }

            if (! $foundMmdbFile) {
                $allFiles = File::allFiles($tempExtractPath);
                $fileList = $allFiles === []
                    ? '[no files found]'
                    : implode(', ', array_map(
                        static fn (SplFileInfo $file): string => $file->getPathname(),
                        $allFiles
                    ));

                throw new Exception("File $fileName not found in unpacked archive $archivePath. Content of $tempExtractPath: $fileList");
            }

            File::ensureDirectoryExists($targetDirectory);
            $finalMmdbPath = $targetDirectory.DIRECTORY_SEPARATOR.$fileName;

            if (File::move($foundMmdbFile, $finalMmdbPath)) {
                $this->info("File $fileName successfully moved to $finalMmdbPath");
            } else {
                throw new Exception("Could not move file $fileName from $foundMmdbFile to $finalMmdbPath");
            }

        } catch (Exception $e) {
            Log::error('Error processing GeoLite archive: '.$e->getMessage()." (Archive: $archivePath)");
            throw $e;
        } finally {
            if (File::isDirectory($tempExtractPath)) {
                File::deleteDirectory($tempExtractPath);
                $this->info("Temporary directory $tempExtractPath deleted.");
            }

            $tarPathAfterDecompress = str_replace('.tar.gz', '.tar', $archivePath);
            if ($tarPathAfterDecompress !== $archivePath && File::exists($tarPathAfterDecompress)) {
                File::delete($tarPathAfterDecompress);
                $this->info("Temporary .tar file $tarPathAfterDecompress deleted.");
            }

            if (File::exists($archivePath)) {
                File::delete($archivePath);
                $this->info("Download archive $archivePath deleted.");
            }
        }
    }
}
