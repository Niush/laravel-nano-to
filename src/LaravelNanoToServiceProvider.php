<?php

namespace Niush\LaravelNanoTo;

use Illuminate\Support\ServiceProvider;

class LaravelNanoToServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        if (class_exists(\Illuminate\Foundation\AliasLoader::class)) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('LaravelNanoTo', 'Niush\LaravelNanoTo\LaravelNanoToFacade');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-nano-to.php'),
            ], 'config');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-nano-to');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-nano-to', function () {
            return new LaravelNanoTo;
        });
    }
}
