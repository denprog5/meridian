<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Exceptions\ConfigurationException;
use Denprog\Meridian\Exceptions\GeoIPUpdaterException;
use Denprog\Meridian\Services\Drivers\GeoIP\MaxMindDatabaseDriver;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PharData;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'meridian:update-geoip-db')]
class UpdateGeoipDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:update-geoip-db';

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
            $licenseKey = config()->string('meridian.geolocation.drivers.maxmind_database.license_key');
            $accountId = config()->string('meridian.geolocation.drivers.maxmind_database.account_id');
            $relativeDbPath = config()->string('meridian.geolocation.drivers.maxmind_database.database_path');
            $url = 'https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz';

            if (empty($licenseKey)) {
                throw new ConfigurationException('MaxMind license key is not configured (meridian.geolocation.drivers.maxmind_database.license_key).');
            }
            if (empty($accountId)) {
                throw new ConfigurationException('MaxMind account id is not configured (meridian.geolocation.drivers.maxmind_database.account_id).');
            }
            if (empty($relativeDbPath)) {
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

            /** @var Response $response */
            $response = Http::withBasicAuth($accountId, $licenseKey)
                ->timeout(300)
                ->get($url);

            if ($response->successful()) {
                $contentDisposition = $response->header('Content-Disposition');
                $filename = 'geoip_download.tar.gz';

                if ($contentDisposition && preg_match('/filename="?([^"]+)"?/', $contentDisposition, $matches)) {
                    $filename = $matches[1];
                }

                $filePath = $absoluteStorageDirectory.DIRECTORY_SEPARATOR.$filename;
                File::put($filePath, $response->body());

                $this->processGeoLiteArchive($filePath, $absoluteStorageDirectory);

            } else {
                throw new GeoIPUpdaterException("Failed to download GeoIP database: {$response->status()} {$response->body()}");
            }

            $this->info('GeoIP database update process finished successfully.');

            return self::SUCCESS;

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
