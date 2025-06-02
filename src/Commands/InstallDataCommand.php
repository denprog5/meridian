<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:install-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs migrations, seeds Meridian data, and publishes the GeoIP database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting Meridian data installation...');

        // 1. Run migrations
        $this->line('Running database migrations...');
        Artisan::call('migrate', ['--force' => true], $this->getOutput());
        $this->info('Migrations completed.');

        // 2. Run MeridianDatabaseSeeder
        $this->line('Seeding Meridian database...');
        Artisan::call('db:seed', [
            '--class' => '\\Denprog\\Meridian\\Database\\Seeders\\MeridianDatabaseSeeder',
            '--force' => true,
        ], $this->getOutput());
        $this->info('Database seeding completed.');

        // 3. Publish GeoLite2-City.mmdb
        $this->line('Publishing GeoLite2-City.mmdb...');
        // Source path is relative to this Command class file, which is in src/Commands
        $sourcePath = __DIR__ . '/../../resources/GeoLite2-City.mmdb';
        $configPathKey = 'meridian.geolocation.drivers.maxmind_database.database_path';
        $relativeDestDir = config($configPathKey);

        if (!is_string($relativeDestDir) || empty($relativeDestDir)) {
            $this->error("GeoIP database path key '{$configPathKey}' is not configured or invalid.");
            return self::FAILURE;
        }

        // The configured path is relative to storage_path()
        $destinationDir = storage_path($relativeDestDir);
        $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . 'GeoLite2-City.mmdb';

        if (!File::exists($sourcePath)) {
            $this->error("Source GeoIP database not found at: {$sourcePath}");
            return self::FAILURE;
        }

        // Ensure the destination directory exists
        if (!File::isDirectory($destinationDir)) {
            File::ensureDirectoryExists($destinationDir);
            $this->comment("Created directory: {$destinationDir}");
        }

        try {
            File::copy($sourcePath, $destinationPath);
            $this->info("GeoIP database published to: {$destinationPath}");
        } catch (Exception $e) {
            $this->error("Failed to publish GeoIP database: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info('Meridian data installation completed successfully.');
        return self::SUCCESS;
    }
}
