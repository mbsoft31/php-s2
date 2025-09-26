<?php

namespace Mbsoft\SemanticScholar;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SemanticScholarServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-semantic-scholar')
            ->hasConfigFile('semantic-scholar');
    }

    /**
     * Register package services.
     */
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__.'/../config/semantic-scholar.php',
            'semantic-scholar'
        );

        $this->app->bind('semantic-scholar', function () {
            return new SemanticScholar();
        });

        $this->app->singleton(SemanticScholar::class, function () {
            return new SemanticScholar();
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/semantic-scholar.php' => config_path('semantic-scholar.php'),
            ], 'semantic-scholar-config');

            $this->publishes([
                __DIR__.'/../config/semantic-scholar.php' => config_path('semantic-scholar.php'),
            ], 'config');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'semantic-scholar',
            SemanticScholar::class,
        ];
    }
}
