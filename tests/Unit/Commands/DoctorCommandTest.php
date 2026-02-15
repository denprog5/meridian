<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Database\Factories\LanguageFactory;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command as CommandAlias;

it('passes doctor checks with valid core configuration data', function (): void {
    config()->set('meridian.base_currency_code', 'USD');
    config()->set('meridian.default_country_iso_code', 'US');
    config()->set('meridian.default_language_code', 'en');
    config()->set('meridian.geolocation.drivers.maxmind_database.database_path', 'framework/testing/meridian');
    config()->set('meridian.geolocation.drivers.maxmind_database.database_filename', 'GeoLite2-City.mmdb');

    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);
    CountryFactory::new()->create(['iso_alpha_2' => 'US']);
    LanguageFactory::new()->create(['code' => 'en', 'is_active' => true]);

    $geoDirectory = storage_path('framework/testing/meridian');
    File::ensureDirectoryExists($geoDirectory);
    File::put($geoDirectory.DIRECTORY_SEPARATOR.'GeoLite2-City.mmdb', 'fake-mmdb-content');

    $this->artisan('meridian:doctor')
        ->expectsOutput('Meridian doctor finished successfully. No issues detected.')
        ->assertExitCode(CommandAlias::SUCCESS);
});

it('fails doctor checks when base currency is invalid', function (): void {
    config()->set('meridian.base_currency_code', 'ZZZ');
    config()->set('meridian.default_country_iso_code', 'US');
    config()->set('meridian.default_language_code', 'en');

    CountryFactory::new()->create(['iso_alpha_2' => 'US']);
    LanguageFactory::new()->create(['code' => 'en', 'is_active' => true]);

    $this->artisan('meridian:doctor')
        ->expectsOutputToContain('Meridian doctor found')
        ->assertExitCode(CommandAlias::FAILURE);
});
