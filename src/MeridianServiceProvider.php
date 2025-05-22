<?php

declare(strict_types=1);

namespace Denprog\Meridian;

use Denprog\Meridian\Commands\InstallCommand;
use Denprog\Meridian\Commands\UpdateExchangeRatesCommand;
use Denprog\Meridian\Contracts\ExchangeRateProvider as ExchangeRateProviderContract;
use Denprog\Meridian\Providers\FrankfurterAppProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class MeridianServiceProvider extends BaseServiceProvider
{
    /**
     * Package base path for brevity.
     */
    protected string $basePath;

    /**
     * Create a new service provider instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->basePath = __DIR__.'/..';
    }

    /**
     * Register any application services.
     *
     * This method is used to bind any services or configurations into the service container.
     * It should not be used to register event listeners, routes, or any other piece of functionality
     * that relies on other services already being registered.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->basePath.'/config/meridian.php',
            'meridian'
        );

        $this->app->singleton(ExchangeRateProviderContract::class, FrankfurterAppProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered,
     * meaning you have access to all other services that have been registered by the framework.
     * It is used for tasks like loading migrations, translations, publishing assets,
     * registering view composers, routes, or event listeners.
     */
    public function boot(): void
    {
        $this->basePath = dirname(__DIR__);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                $this->basePath.'/config/meridian.php' => config_path('meridian.php'),
            ], 'meridian-config');

            // Publish migrations
            $this->publishes([
                $this->basePath.'/database/migrations' => database_path('migrations'),
            ], 'meridian-migrations');

            // Optionally, publish language files
             $this->publishes([
                 $this->basePath.'/lang' => lang_path('vendor/meridian'),
             ], 'meridian-lang');
        }

        // Load translations if they exist
        $this->loadTranslationsFrom($this->basePath.'/lang', 'meridian');

        $this->registerCommands();
    }

    /**
     * Register commands for the package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UpdateExchangeRatesCommand::class,
            ]);
        }
    }
}
