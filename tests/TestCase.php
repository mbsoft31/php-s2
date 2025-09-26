<?php

namespace Mbsoft\SemanticScholar\Tests;

use Mbsoft\SemanticScholar\SemanticScholarServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment configuration
        config([
            'semantic-scholar.api_key' => 'test-api-key',
            'semantic-scholar.base_url' => 'https://api.semanticscholar.org/graph/v1',
            'semantic-scholar.timeout' => 10,
            'semantic-scholar.logging.enabled' => false,
            'semantic-scholar.debug.log_requests' => false,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            SemanticScholarServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('cache.default', 'array');
    }

    /**
     * Mock a successful API response.
     */
    protected function mockSuccessfulResponse(array $data = []): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response($data, 200),
        ]);
    }

    /**
     * Mock a failed API response.
     */
    protected function mockFailedResponse(int $status = 500, array $data = []): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response($data, $status),
        ]);
    }

    /**
     * Mock a rate limited response.
     */
    protected function mockRateLimitedResponse(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'message' => 'Rate limit exceeded',
            ], 429, ['Retry-After' => '60']),
        ]);
    }
}
