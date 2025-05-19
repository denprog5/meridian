<?php

declare(strict_types=1);

namespace Denprog\Meridian;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class MeridianServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/meridian.php',
            'meridian'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/meridian.php', 'meridian'
        );

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'meridian');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'meridian-migrations');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('meridian'),
        ], 'meridian-translations');

        $this->publishes([
            __DIR__.'/../config/meridian.php' => config_path('meridian.php'),
        ], 'meridian-config');
    }
}
