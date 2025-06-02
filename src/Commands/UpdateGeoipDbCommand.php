<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Exceptions\ConfigurationException;
use Denprog\Meridian\Exceptions\GeoIPUpdaterException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PharData;

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
    public function handle(ConfigRepository $config, LoggerInterface $logger): int
    {
        $this->info('Starting GeoIP database update process...');

        try {
            $licenseKey = $config->string('meridian.geolocation.drivers.maxmind_database.license_key');
            $accountId = $config->string('meridian.geolocation.drivers.maxmind_database.account_id');
            $relativeDbPath = $config->string('meridian.geolocation.drivers.maxmind_database.database_path');
            $url = 'https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz';

            if (empty($licenseKey)) {
                throw new ConfigurationException('MaxMind license key is not configured (meridian.geolocation.drivers.maxmind_database.license_key).');
            }
            if (empty($relativeDbPath)) {
                throw new ConfigurationException('MaxMind database storage path is not configured (meridian.geolocation.drivers.maxmind_database.database_path).');
            }

            $absoluteStorageDirectory = storage_path('app/'.mb_ltrim(dirname($relativeDbPath), '/\\'));

            if (! is_dir($absoluteStorageDirectory)) {
                if (! mkdir($absoluteStorageDirectory, 0755, true)) {
                    throw new GeoIPUpdaterException("Failed to create GeoIP database storage directory: $absoluteStorageDirectory");
                }
                $this->line("Created storage directory: $absoluteStorageDirectory");
            }
            if (! is_writable($absoluteStorageDirectory)) {
                throw new GeoIPUpdaterException("GeoIP database storage directory is not writable: $absoluteStorageDirectory");
            }

            $response = Http::withBasicAuth($accountId, $licenseKey)
                ->timeout(300)
                ->get($url);

            if ($response->successful()) {
                $contentDisposition = $response->header('Content-Disposition');
                $filename = 'geoip_download.zip';

                if ($contentDisposition) {
                    if (preg_match('/filename="?([^"]+)"?/', $contentDisposition, $matches)) {
                        $filename = $matches[1];
                    }
                }

                $filePath = $absoluteStorageDirectory . '/' . $filename;
                File::put($filePath, $response->body());

                $this->processGeoLiteArchive($filePath, $absoluteStorageDirectory);

            } else {
                throw new GeoIPUpdaterException("Failed to download GeoIP database: {$response->status()} {$response->body()}");
            }

            $this->info('GeoIP database update process finished successfully.');

            return self::SUCCESS;

        } catch (ConfigurationException $e) {
            $this->error('Configuration error: '.$e->getMessage());
            $logger->error('GeoIP DB Update Configuration Error: '.$e->getMessage());

            return self::FAILURE;
        } catch (GeoIPUpdaterException $e) {
            $this->error('GeoIP Updater error: '.$e->getMessage());
            $logger->error('GeoIP DB Updater Error: '.$e->getMessage());

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error('An unexpected error occurred: '.$e->getMessage());
            $logger->error('GeoIP DB Update Unexpected Error: '.$e->getMessage(), ['exception' => $e]);

            return self::FAILURE;
        }
    }

    /**
     * Распаковывает архив GeoLite2 (.tar.gz), находит .mmdb файл и перемещает его
     * в указанную директорию с заменой.
     *
     * @param string $archivePath Полный путь к скачанному .tar.gz файлу.
     * @param string $targetDirectory Директория, куда нужно поместить .mmdb файл (например, storage_path('app')).
     * @throws Exception Если возникают ошибки при обработке.
     */
    private function processGeoLiteArchive(
        string $archivePath,
        string $targetDirectory,
    ): void
    {
        $tempExtractPath = $targetDirectory;

        try {
            if (!File::exists($archivePath)) {
                throw new Exception("Архив не найден: $archivePath");
            }

            File::ensureDirectoryExists($tempExtractPath);

            $phar = new PharData($archivePath);
            $phar->decompress();

            $tarPath = str_replace('.tar.gz', '.tar', $archivePath);
            if (!File::exists($tarPath)) {
                $tarPath = $archivePath;
            }

            $pharTar = new PharData($tarPath);
            $pharTar->extractTo($tempExtractPath, null, true);

            $this->info("Архив $archivePath распакован во временную директорию $tempExtractPath");

            $foundMmdbFile = null;
            $filesAndFolders = File::directories($tempExtractPath);

            if (!empty($filesAndFolders)) {
                $potentialMmdbDir = $filesAndFolders[0];
                $expectedMmdbPathInArchive = $potentialMmdbDir . '/' . 'GeoLite2-City.mmdb';
                if (File::exists($expectedMmdbPathInArchive)) {
                    $foundMmdbFile = $expectedMmdbPathInArchive;
                }
            }

            if (!$foundMmdbFile) {
                $allFiles = File::allFiles($tempExtractPath);
                foreach ($allFiles as $file) {
                    if ($file->getFilename() === 'GeoLite2-City.mmdb') {
                        $foundMmdbFile = $file->getRealPath();
                        break;
                    }
                }
            }

            if (!$foundMmdbFile) {
                throw new Exception("Файл GeoLite2-City.mmdb не найден в распакованном архиве $archivePath. Содержимое $tempExtractPath: " . implode(', ', File::allFiles($tempExtractPath)));
            }

            File::ensureDirectoryExists($targetDirectory);
            $finalMmdbPath = rtrim($targetDirectory, '/') . '/' . 'GeoLite2-City.mmdb';

            if (File::move($foundMmdbFile, $finalMmdbPath)) {
                $this->info("Файл GeoLite2-City.mmdb успешно перемещен в $finalMmdbPath с заменой.");
            } else {
                throw new Exception("Не удалось переместить файл $foundMmdbFile в $finalMmdbPath");
            }

        } catch (Exception $e) {
            Log::error("Ошибка при обработке архива GeoLite: " . $e->getMessage() . " (Архив: $archivePath)");
            throw $e;
        } finally {
            if (File::isDirectory($tempExtractPath)) {
                File::deleteDirectory($tempExtractPath);
                $this->info("Временная директория $tempExtractPath удалена.");
            }

            $tarPathAfterDecompress = str_replace('.tar.gz', '.tar', $archivePath);
            if ($tarPathAfterDecompress !== $archivePath && File::exists($tarPathAfterDecompress)) {
                File::delete($tarPathAfterDecompress);
                $this->info("Промежуточный .tar файл $tarPathAfterDecompress удален.");
            }

            if (File::exists($archivePath)) {
                File::delete($archivePath);
                $this->info("Скачанный архив $archivePath удален после обработки.");
            }
        }
    }
}
