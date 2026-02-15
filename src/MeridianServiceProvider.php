<?php

declare(strict_types=1);

namespace Denprog\Meridian;

use Denprog\Meridian\Commands\DoctorCommand;
use Denprog\Meridian\Commands\InstallCommand;
use Denprog\Meridian\Commands\InstallDataCommand;
use Denprog\Meridian\Commands\UpdateExchangeRatesCommand;
use Denprog\Meridian\Commands\UpdateGeoipDbCommand;
use Denprog\Meridian\Contracts\CountryServiceContract;
use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Contracts\ExchangeRateProvider as ExchangeRateProviderContract;
use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\Contracts\GeoLocationServiceContract;
use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Denprog\Meridian\Providers\FrankfurterAppProvider;
use Denprog\Meridian\Services\CountryService;
use Denprog\Meridian\Services\CurrencyConverterService;
use Denprog\Meridian\Services\CurrencyService;
use Denprog\Meridian\Services\Drivers\GeoIP\MaxMindDatabaseDriver;
use Denprog\Meridian\Services\GeoLocationService;
use Denprog\Meridian\Services\LanguageService;
use Denprog\Meridian\Services\UpdateExchangeRateService;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Service provider for the Meridian package.
 *
 * Registers all package services, configurations, commands, and publishable assets.
 */
class MeridianServiceProvider extends BaseServiceProvider
{
    /**
     * All console commands provided by the package.
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
        InstallCommand::class,
        InstallDataCommand::class,
        DoctorCommand::class,
        UpdateExchangeRatesCommand::class,
        UpdateGeoipDbCommand::class,
    ];

    /**
     * Register any application services.
     *
     * This method is used to bind any services or configurations into the service container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'meridian');

        $this->registerCoreServices();
        $this->registerGeoLocationServices();
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom($this->basePath('lang'), 'meridian');

        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
            $this->commands($this->commands);
        }
    }

    /**
     * Get the base path for the package.
     */
    private function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__);

        return $path !== '' ? $base.'/'.$path : $base;
    }

    /**
     * Get the path to the package configuration file.
     */
    private function configPath(): string
    {
        return $this->basePath('config/meridian.php');
    }

    /**
     * Register core package services.
     */
    private function registerCoreServices(): void
    {
        $this->app->singleton(ExchangeRateProviderContract::class, FrankfurterAppProvider::class);
        $this->app->scoped(CountryServiceContract::class, CountryService::class);
        $this->app->scoped(CurrencyServiceContract::class, CurrencyService::class);
        $this->app->scoped(LanguageServiceContract::class, LanguageService::class);
        $this->app->scoped(CurrencyConverterContract::class, CurrencyConverterService::class);
        $this->app->singleton(UpdateExchangeRateContract::class, UpdateExchangeRateService::class);
    }

    /**
     * Register GeoLocation-related services.
     */
    private function registerGeoLocationServices(): void
    {
        $this->app->singleton(GeoIpDriverContract::class, MaxMindDatabaseDriver::class);
        $this->app->singleton(GeoLocationServiceContract::class, GeoLocationService::class);
    }

    /**
     * Register publishable assets for the package.
     */
    private function registerPublishables(): void
    {
        // Configuration - tagged with both specific and group tags
        $this->publishes([
            $this->configPath() => config_path('meridian.php'),
        ], ['meridian', 'meridian-config']);

        // Migrations - using Laravel 12's publishesMigrations for proper timestamp handling
        $this->publishesMigrations([
            $this->basePath('database/migrations') => database_path('migrations'),
        ], ['meridian', 'meridian-migrations']);

        // Language files
        $this->publishes([
            $this->basePath('lang') => lang_path('vendor/meridian'),
        ], ['meridian', 'meridian-lang']);
    }
}
