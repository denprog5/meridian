<?php

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Exceptions\ConfigurationException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
use Tronovav\GeoIP2Update\Client as GeoIPUpdaterClient;
use Tronovav\GeoIP2Update\Exception\Exception as GeoIPUpdaterException;

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
     *
     * @param ConfigRepository $config
     * @param LoggerInterface $logger
     * @return int
     */
    public function handle(ConfigRepository $config, LoggerInterface $logger): int
    {
        $this->info('Starting GeoIP database update process...');

        try {
            $licenseKey = $config->get('meridian.geolocation.drivers.maxmind_database.license_key');
            $accountId = $config->get('meridian.geolocation.drivers.maxmind_database.account_id'); // Optional
            $dbPath = $config->get('meridian.geolocation.drivers.maxmind_database.database_path');
            $editions = $config->get('meridian.geolocation.drivers.maxmind_database.editions', ['GeoLite2-City']);

            if (empty($licenseKey)) {
                throw new ConfigurationException('MaxMind license key is not configured (meridian.geolocation.drivers.maxmind_database.license_key).');
            }
            if (empty($dbPath)) {
                throw new ConfigurationException('MaxMind database storage path is not configured (meridian.geolocation.drivers.maxmind_database.database_path).');
            }
            if (empty($editions) || !is_array($editions)) {
                throw new ConfigurationException('MaxMind database editions are not configured correctly (meridian.geolocation.drivers.maxmind_database.editions).');
            }

            // The tronovav/geoip2-update library expects the *directory* where the .mmdb files will be stored.
            // Our $dbPath config currently points to the *file* itself.
            // We need to ensure the directory exists and is writable.
            $storageDirectory = dirname($dbPath);
            if (!is_dir($storageDirectory)) {
                if (!mkdir($storageDirectory, 0755, true)) {
                    throw new GeoIPUpdaterException("Failed to create GeoIP database storage directory: {$storageDirectory}");
                }
                $this->line("Created storage directory: {$storageDirectory}");
            }
            if (!is_writable($storageDirectory)) {
                 throw new GeoIPUpdaterException("GeoIP database storage directory is not writable: {$storageDirectory}");
            }

            $clientOptions = [
                'license_key' => $licenseKey,
                'dir' => $storageDirectory, // Use the directory path
                'editions' => $editions,
            ];

            if (!empty($accountId)) {
                $clientOptions['account_id'] = $accountId;
            }

            $client = new GeoIPUpdaterClient($clientOptions);
            $client->run();

            $updatedFiles = $client->updated();
            $errors = $client->errors();

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->error("Error updating GeoIP database: {$error}");
                    $logger->error('GeoIP DB Update Error: ' . $error);
                }
                $this->warn('GeoIP database update process completed with errors.');
                return Command::FAILURE;
            }

            if (!empty($updatedFiles)) {
                $this->info('Successfully updated the following GeoIP database files:');
                foreach ($updatedFiles as $file) {
                    $this->line("- {$file}");
                    $logger->info('GeoIP DB Updated: ' . $file);
                }
            } else {
                $this->info('GeoIP databases are already up to date.');
                $logger->info('GeoIP DB Check: Databases are up to date.');
            }

            $this->info('GeoIP database update process finished successfully.');
            return Command::SUCCESS;

        } catch (ConfigurationException $e) {
            $this->error("Configuration error: " . $e->getMessage());
            $logger->error('GeoIP DB Update Configuration Error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (GeoIPUpdaterException $e) {
            $this->error("GeoIP Updater error: " . $e->getMessage());
            $logger->error('GeoIP DB Updater Error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("An unexpected error occurred: " . $e->getMessage());
            $logger->error('GeoIP DB Update Unexpected Error: ' . $e->getMessage(), ['exception' => $e]);
            return Command::FAILURE;
        }
    }
}
