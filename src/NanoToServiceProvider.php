<?php

namespace Niush\NanoTo;

use Illuminate\Support\ServiceProvider;

class NanoToServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        if (class_exists(\Illuminate\Foundation\AliasLoader::class)) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('NanoTo', 'Niush\NanoTo\NanoToFacade');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('nano-to.php'),
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
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'nano-to');

        // Register the main class to use with the facade
        $this->app->singleton('nano-to', function () {
            return new NanoTo;
        });
    }
}
