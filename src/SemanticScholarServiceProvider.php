<?php
// src/SemanticScholarServiceProvider.php (Enhanced)

namespace Mbsoft\SemanticScholar;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Mbsoft\SemanticScholar\Commands\SemanticScholarCommand;
use Mbsoft\SemanticScholar\Http\Client;

class SemanticScholarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/semantic-scholar.php', 'semantic-scholar');

        $this->app->singleton('semantic-scholar', function ($app) {
            return new SemanticScholar($app->make(Client::class));
        });

        $this->app->singleton(Client::class, function ($app) {
            return new Client($app['config']['semantic-scholar']);
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/semantic-scholar.php' => config_path('semantic-scholar.php'),
        ], 'semantic-scholar-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SemanticScholarCommand::class,
            ]);
        }

        // Register health check routes in development
        if ($this->app->environment(['local', 'testing'])) {
            $this->registerHealthRoutes();
        }

        // Register cache tags for cache management
        $this->registerCacheTags();
    }

    protected function registerHealthRoutes(): void
    {
        Route::prefix('semantic-scholar')
            ->group(function () {
                Route::get('health', function () {
                    try {
                        $paper = app('semantic-scholar')->papers()
                            ->findByDoi('10.1093/mind/lix.236.433');

                        return response()->json([
                            'status' => 'healthy',
                            'test_paper' => $paper ? $paper->getTitle() : null,
                            'timestamp' => now(),
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'unhealthy',
                            'error' => $e->getMessage(),
                            'timestamp' => now(),
                        ], 500);
                    }
                });
            });
    }

    protected function registerCacheTags(): void
    {
        // Register cache tags for organized cache management
        $this->app->booted(function () {
            if (config('semantic-scholar.cache.tags')) {
                cache()->tags(['semantic-scholar']);
            }
        });
    }
}
