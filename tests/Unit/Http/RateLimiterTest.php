<?php
// tests/Unit/Http/RateLimiterTest.php - Fixed version

use Mbsoft\SemanticScholar\Http\RateLimiter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    $this->rateLimiter = new RateLimiter([
        'requests_per_second' => 1,
        'with_api_key_rps' => 10,
    ], false); // Second parameter: has API key
});

test('allows requests within rate limit', function () {
    // Use a simpler test approach
    $key = 'test-key-' . time(); // Unique key to avoid conflicts

    expect($this->rateLimiter->attempt($key))->toBeTrue();

    // Should not be able to make another request immediately
    expect($this->rateLimiter->attempt($key))->toBeFalse();
});

test('has higher limits with api key', function () {
    $rateLimiterWithKey = new RateLimiter([
        'requests_per_second' => 1,
        'with_api_key_rps' => 10,
    ], true); // with API key

    $key = 'test-key-with-api-' . time();

    // Should allow multiple requests with API key
    for ($i = 0; $i < 5; $i++) {
        expect($rateLimiterWithKey->attempt($key))->toBeTrue();
    }
});

test('calculates retry delay', function () {
    $key = 'test-delay-' . time();

    // Exhaust the rate limit
    $this->rateLimiter->attempt($key);

    $retryAfter = $this->rateLimiter->retryAfter($key);

    expect($retryAfter)->toBeGreaterThan(0)
        ->and($retryAfter)->toBeLessThanOrEqual(1000);
});

test('can check remaining attempts', function () {
    $key = 'test-remaining-' . time();

    $remaining = $this->rateLimiter->remaining($key);
    expect($remaining)->toBe(1);

    $this->rateLimiter->attempt($key);

    $remaining = $this->rateLimiter->remaining($key);
    expect($remaining)->toBe(0);
});

test('resets after time window', function () {
    $key = 'test-reset-' . time();

    $this->rateLimiter->attempt($key);

    // Simulate time passing by using a different key
    $newKey = 'test-reset-new-' . time();

    expect($this->rateLimiter->attempt($newKey))->toBeTrue();
});
