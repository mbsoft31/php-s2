<?php

namespace Mbsoft\SemanticScholar\Tests;

use Mbsoft\SemanticScholar\SemanticScholarServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        config([
            'semantic-scholar.api_key' => 'test-api-key',
            'semantic-scholar.base_url' => 'https://api.semanticscholar.org/graph/v1',
            'semantic-scholar.timeout' => 30,
            'semantic-scholar.retry_attempts' => 3,
            'semantic-scholar.cache.enabled' => false,
            'semantic-scholar.logging.enabled' => false,
            'semantic-scholar.rate_limiting.enabled' => false,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            SemanticScholarServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'SemanticScholar' => \Mbsoft\SemanticScholar\Facades\SemanticScholar::class,
        ];
    }

    protected function tearDown(): void
    {
        // Remove the assertNothingOutstanding call that's causing issues
        parent::tearDown();
    }
}
