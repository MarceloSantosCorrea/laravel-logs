<?php

namespace MarceloCorrea\LaravelLogs;

use Illuminate\Support\ServiceProvider;

class LogsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $path = realpath(__DIR__.'/../config/laravel-logs.php');
        $this->mergeConfigFrom($path, 'laravel-logs');

        $this->app->bind('logs', function () {
            return new \MarceloCorrea\LaravelLogs\LaravelLogs();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../config/laravel-logs.php');
        $this->publishes([$path => config_path('laravel-logs.php')]);

        $migrationPath = __DIR__.'/../database/migrations';
        $this->publishes([
            $migrationPath => base_path('database/migrations'),
        ], 'migrations');
    }
}
