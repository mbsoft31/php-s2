<?php
// tests/Unit/Http/ClientTest.php

use Mbsoft\SemanticScholar\Http\Client;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->config = [
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.semanticscholar.org/graph/v1',
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000,
        'rate_limiting' => [
            'enabled' => false, // Disable for tests
            'requests_per_second' => 1,
            'with_api_key_rps' => 10,
        ]
    ];

    $this->client = new Client($this->config); // Now passes array instead of RateLimiter
    Http::fake();
});

test('can make successful get request', function () {
    $expectedResponse = ['data' => ['test' => 'value']];

    Http::fake([
        'api.semanticscholar.org/*' => Http::response($expectedResponse, 200)
    ]);

    $response = $this->client->get('paper/search', ['query' => 'test']);

    expect($response)->toBe($expectedResponse);

    Http::assertSent(function ($request) {
        return $request->hasHeader('x-api-key', 'test-api-key') &&
            str_contains($request->url(), 'paper/search') &&
            $request['query'] === 'test';
    });
});

test('handles 404 errors gracefully', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response('Not Found', 404)
    ]);

    expect(fn() => $this->client->get('paper/non-existent'))
        ->toThrow(SemanticScholarException::class, 'Resource not found');
});

test('handles rate limit errors', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'error' => 'Rate limit exceeded'
        ], 429)
    ]);

    $exception = null;
    try {
        $this->client->get('paper/test');
    } catch (SemanticScholarException $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->isRateLimit())->toBeTrue();
});

test('handles unauthorized errors', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'error' => 'Invalid API key'
        ], 401)
    ]);

    $exception = null;
    try {
        $this->client->get('paper/test');
    } catch (SemanticScholarException $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->isUnauthorized())->toBeTrue();
});

test('retries failed requests', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::sequence()
            ->push('Server Error', 500)
            ->push('Server Error', 500)
            ->push(['data' => ['success' => true]], 200)
    ]);

    $response = $this->client->retry(3)->get('paper/test');

    expect($response)->toBe(['data' => ['success' => true]]);

    // Should have made 3 requests (2 failures + 1 success)
    Http::assertSentCount(3);
});

test('respects timeout settings', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['data' => []], 200)
    ]);

    $this->client->timeout(60)->get('paper/test');

    Http::assertSent(function ($request) {
        return $request->timeout() === 60;
    });
});

test('can set custom headers', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['data' => []], 200)
    ]);

    $customHeaders = ['Custom-Header' => 'custom-value'];
    $this->client->get('paper/test', [], $customHeaders);

    Http::assertSent(function ($request) use ($customHeaders) {
        return $request->hasHeader('Custom-Header', 'custom-value') &&
            $request->hasHeader('x-api-key', 'test-api-key');
    });
});

test('handles network errors', function () {
    Http::fake(function () {
        throw new \Exception('Network connection failed');
    });

    expect(fn() => $this->client->get('paper/test'))
        ->toThrow(SemanticScholarException::class, 'Network error');
});

test('can check health', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['status' => 'healthy'], 200)
    ]);

    $isHealthy = $this->client->healthCheck();

    expect($isHealthy)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'paper/DOI:10.1093/mind/lix.236.433');
    });
});

test('handles unhealthy responses', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response('Service Unavailable', 503)
    ]);

    $isHealthy = $this->client->healthCheck();

    expect($isHealthy)->toBeFalse();
});
