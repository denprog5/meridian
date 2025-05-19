<?php

declare(strict_types=1);

namespace Denprog\Meridian;

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
        // Configuration is merged in register(), no need to call mergeConfigFrom() here.

        // Load translations for the package.
        $this->loadTranslationsFrom($this->basePath.'/lang', 'meridian');

        // Load migrations for the package.
        // These migrations will be run automatically when `php artisan migrate` is executed.
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->basePath.'/database/migrations');
        }

        // Define assets that can be published by the user.
        $this->publishableAssets();
    }

    /**
     * Define the publishable assets for the package.
     *
     * This method groups all publishable assets (configurations, migrations, translations, etc.)
     * to keep the boot method cleaner.
     */
    protected function publishableAssets(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish migrations, allowing users to customize them if needed.
            $this->publishes([
                $this->basePath.'/database/migrations' => database_path('migrations'),
            ], 'meridian-migrations');

            // Publish translation files, allowing users to override them.
            $this->publishes([
                $this->basePath.'/lang' => $this->app->langPath('vendor/meridian'),
            ], 'meridian-translations');

            // Publish the configuration file, allowing users to override default settings.
            $this->publishes([
                $this->basePath.'/config/meridian.php' => config_path('meridian.php'),
            ], 'meridian-config');

            // Example for publishing views (if any in the future)
            // $this->publishes([
            //     $this->basePath . '/resources/views' => resource_path('views/vendor/meridian'),
            // ], 'meridian-views');
        }
    }
}
