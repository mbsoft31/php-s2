<?php

namespace Mbsoft\SemanticScholar;

use Illuminate\Support\ServiceProvider;
use Mbsoft\SemanticScholar\Http\Client;
use Mbsoft\SemanticScholar\Builders\PaperBuilder;
use Mbsoft\SemanticScholar\Builders\AuthorBuilder;
use Mbsoft\SemanticScholar\Commands\SemanticScholarCommand;

class SemanticScholarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__.'/../config/semantic-scholar.php', 'semantic-scholar');

        // Register HTTP Client as singleton
        $this->app->singleton(Client::class, function ($app) {
            return new Client($app['config']['semantic-scholar']);
        });

        // Register main SemanticScholar service
        $this->app->singleton('semantic-scholar', function ($app) {
            return new SemanticScholar($app->make(Client::class));
        });

        // Register specialized builders
        $this->app->bind(PaperBuilder::class, function ($app) {
            return new PaperBuilder($app->make(Client::class));
        });

        $this->app->bind(AuthorBuilder::class, function ($app) {
            return new AuthorBuilder($app->make(Client::class));
        });

        // Register builder factory methods
        $this->app->bind('semantic-scholar.papers', function ($app) {
            return $app->make(PaperBuilder::class);
        });

        $this->app->bind('semantic-scholar.authors', function ($app) {
            return $app->make(AuthorBuilder::class);
        });
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/semantic-scholar.php' => config_path('semantic-scholar.php'),
        ], 'semantic-scholar-config');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SemanticScholarCommand::class,
            ]);
        }

        // Register health check route in development
        if ($this->app->environment(['local', 'testing'])) {
            $this->registerHealthRoute();
        }
    }

    protected function registerHealthRoute(): void
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__.'/../routes/health.php';
        }
    }
}
