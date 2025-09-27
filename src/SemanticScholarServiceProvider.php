<?php

namespace Mbsoft\SemanticScholar;

use Exception;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Mbsoft\SemanticScholar\Builders\AuthorBuilder;
use Mbsoft\SemanticScholar\Builders\PaperBuilder;
use Mbsoft\SemanticScholar\Commands\SemanticScholarCommand;
use Mbsoft\SemanticScholar\Http\Client;

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

        // Register Builder with Client dependency injection
        $this->app->bind(Builder::class, function ($app) {
            return new Builder($app->make(Client::class));
        });

        // Register specialized builders if they exist
        if (class_exists(PaperBuilder::class)) {
            $this->app->bind(PaperBuilder::class, function ($app) {
                return new PaperBuilder($app->make(Client::class));
            });
        }

        if (class_exists(AuthorBuilder::class)) {
            $this->app->bind(AuthorBuilder::class, function ($app) {
                return new AuthorBuilder($app->make(Client::class));
            });
        }

        // Register builder factory methods
        $this->app->bind('semantic-scholar.papers', function ($app) {
            return $app->make(Builder::class)->endpoint('paper');
        });

        $this->app->bind('semantic-scholar.authors', function ($app) {
            return $app->make(Builder::class)->endpoint('author');
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

        // Register health check routes in development
        if ($this->app->environment(['local', 'testing'])) {
            $this->registerHealthRoutes();
        }

        // Register cache tags for cache management - ONLY if tagging is supported
        $this->registerCacheTags();
    }

    /**
     * Register health check routes
     */
    protected function registerHealthRoutes(): void
    {
        Route::prefix('semantic-scholar')
            ->name('semantic-scholar.')
            ->group(function () {
                Route::get('health', function () {
                    try {
                        $isHealthy = app('semantic-scholar')->test();

                        return response()->json([
                            'status' => $isHealthy ? 'healthy' : 'unhealthy',
                            'service' => 'semantic-scholar',
                            'timestamp' => now(),
                            'api_key_configured' => !empty(config('semantic-scholar.api_key')),
                            'rate_limit' => config('semantic-scholar.api_key') ? '100 req/sec' : '1 req/sec',
                        ], $isHealthy ? 200 : 500);

                    } catch (Exception $e) {
                        return response()->json([
                            'status' => 'error',
                            'service' => 'semantic-scholar',
                            'error' => $e->getMessage(),
                            'timestamp' => now(),
                        ], 500);
                    }
                })->name('health');

                Route::get('status', function () {
                    $config = config('semantic-scholar');

                    return response()->json([
                        'service' => 'semantic-scholar',
                        'version' => '1.0.0',
                        'api_key_configured' => !empty($config['api_key']),
                        'base_url' => $config['base_url'],
                        'rate_limit' => $config['api_key'] ?
                            $config['rate_limiting']['with_api_key_rps'] . ' req/sec' :
                            $config['rate_limiting']['requests_per_second'] . ' req/sec',
                        'cache_enabled' => $config['cache']['enabled'],
                        'cache_driver' => $config['cache']['driver'],
                        'retry_attempts' => $config['retry_attempts'],
                        'timeout' => $config['timeout'],
                    ]);
                })->name('status');
            });
    }

    /**
     * Register cache tags for organized cache management
     * Only if cache driver supports tagging and tagging is enabled
     */
    protected function registerCacheTags(): void
    {
        $this->app->booted(function () {
            // Only attempt to use cache tags if configuration supports it
            $cacheConfig = config('semantic-scholar.cache', []);
            $cacheEnabled = $cacheConfig['enabled'] ?? false;
            $tagsEnabled = $cacheConfig['tags'] ?? false;
            
            if (!$cacheEnabled || !$tagsEnabled) {
                return;
            }

            try {
                // Check if the current cache store supports tagging
                $cacheStore = cache()->getStore();
                
                // Only Redis and Memcached drivers support tagging
                $supportsTagging = method_exists($cacheStore, 'tags') && (
                    $cacheStore instanceof \Illuminate\Cache\RedisStore ||
                    $cacheStore instanceof \Illuminate\Cache\MemcachedStore
                );
                
                if ($supportsTagging) {
                    cache()->tags(['semantic-scholar']);
                }
                
            } catch (Exception $e) {
                // Silently fail if cache tagging is not supported
                // This prevents the application from crashing in test environments
                // where array cache driver is used
            }
        });
    }
}