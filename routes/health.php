<?php

use Illuminate\Support\Facades\Route;
use Mbsoft\SemanticScholar\Facades\SemanticScholar;

Route::prefix('semantic-scholar')
    ->name('semantic-scholar.')
    ->group(function () {
        Route::get('health', function () {
            try {
                $isHealthy = SemanticScholar::test();

                return response()->json([
                    'status' => $isHealthy ? 'healthy' : 'unhealthy',
                    'service' => 'semantic-scholar',
                    'timestamp' => now(),
                    'api_key_configured' => !empty(config('semantic-scholar.api_key')),
                ], $isHealthy ? 200 : 500);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'service' => 'semantic-scholar',
                    'error' => $e->getMessage(),
                    'timestamp' => now(),
                ], 500);
            }
        })->name('health');
    });
