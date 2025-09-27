<?php
// tests/TestCase.php - Updated with Data config

namespace Mbsoft\SemanticScholar\Tests;

use Mbsoft\SemanticScholar\SemanticScholarServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration for both packages
        config([
            // Semantic Scholar config
            'semantic-scholar.api_key' => 'test-api-key',
            'semantic-scholar.base_url' => 'https://api.semanticscholar.org/graph/v1',
            'semantic-scholar.timeout' => 30,
            'semantic-scholar.retry_attempts' => 3,
            'semantic-scholar.cache.enabled' => false,
            'semantic-scholar.logging.enabled' => false,
            'semantic-scholar.rate_limiting.enabled' => true, // Keep enabled for testing

            // Spatie Laravel Data config - CRITICAL FIX
            'data' => [
                'validation_strategy' => 'only_requests',
                'max_transformation_depth' => 512,
                'max_cast_depth' => 512,
                'rule_inferrers' => [],
                'normalizers' => [
                    \Spatie\LaravelData\Normalizers\ArrayNormalizer::class,
                ],
                'transformers' => [],
                'casts' => [],
                'wrap' => null,
                'var_dumper_caster_mode' => 'disabled',
                'throw_when_invalid_partial' => false,
            ],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            SemanticScholarServiceProvider::class,
            \Spatie\LaravelData\LaravelDataServiceProvider::class, // Add Data provider
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
        parent::tearDown();
    }
}
