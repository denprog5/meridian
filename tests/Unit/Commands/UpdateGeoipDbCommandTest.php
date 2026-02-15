<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandAlias;

beforeEach(function (): void {
    config()->set('meridian.geolocation.drivers.maxmind_database.license_key', 'test-license-key');
    config()->set('meridian.geolocation.drivers.maxmind_database.account_id', '123456');
    config()->set('meridian.geolocation.drivers.maxmind_database.database_path', 'framework/testing/meridian');
});

it('supports dry-run mode for geoip update command', function (): void {
    $this->artisan('meridian:update-geoip-db --dry-run')
        ->expectsOutput('Starting GeoIP database update process...')
        ->expectsOutput('Dry run mode enabled. No files were downloaded or replaced.')
        ->assertExitCode(CommandAlias::SUCCESS);
});

it('skips geoip update when lock cannot be acquired', function (): void {
    Cache::shouldReceive('lock->get')->once()->andReturn(false);

    $this->artisan('meridian:update-geoip-db')
        ->expectsOutput('Starting GeoIP database update process...')
        ->expectsOutput('Another GeoIP update is already running. Skipping.')
        ->assertExitCode(CommandAlias::SUCCESS);
});

it('fails with invalid retries option for geoip update command', function (): void {
    $this->artisan('meridian:update-geoip-db --retries=0')
        ->expectsOutput('Input error: Option --retries must be a positive integer.')
        ->assertExitCode(CommandAlias::FAILURE);
});
