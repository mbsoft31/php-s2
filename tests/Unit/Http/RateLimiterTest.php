<?php

use Mbsoft\SemanticScholar\Http\RateLimiter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    $this->rateLimiter = new RateLimiter([
        'requests_per_second' => 2, // Use 2 to make testing easier
        'with_api_key_rps' => 10,
    ], false);
});

test('allows requests within rate limit', function () {
    $key = 'test-' . time() . '-' . rand(1000, 9999);

    expect($this->rateLimiter->attempt($key))->toBeTrue();
    expect($this->rateLimiter->attempt($key))->toBeTrue(); // Should allow 2 requests
    expect($this->rateLimiter->attempt($key))->toBeFalse(); // Third should fail
});

test('has higher limits with api key', function () {
    $rateLimiterWithKey = new RateLimiter([
        'requests_per_second' => 2,
        'with_api_key_rps' => 10,
    ], true); // with API key

    $key = 'test-api-' . time() . '-' . rand(1000, 9999);

    // Should allow more requests with API key
    for ($i = 0; $i < 5; $i++) {
        expect($rateLimiterWithKey->attempt($key))->toBeTrue();
    }
});

test('calculates retry delay', function () {
    $key = 'test-delay-' . time() . '-' . rand(1000, 9999);

    // Exhaust the rate limit
    $this->rateLimiter->attempt($key);
    $this->rateLimiter->attempt($key);
    $this->rateLimiter->attempt($key); // This should fail

    $retryAfter = $this->rateLimiter->retryAfter($key);

    expect($retryAfter)->toBeGreaterThanOrEqual(0)
        ->and($retryAfter)->toBeLessThanOrEqual(1000);
});

test('can check remaining attempts', function () {
    $key = 'test-remaining-' . time() . '-' . rand(1000, 9999);

    expect($this->rateLimiter->remaining($key))->toBe(2); // Starts with 2

    $this->rateLimiter->attempt($key);
    expect($this->rateLimiter->remaining($key))->toBe(1); // Now 1 left

    $this->rateLimiter->attempt($key);
    expect($this->rateLimiter->remaining($key))->toBe(0); // Now 0 left
});

test('can clear rate limit', function () {
    $key = 'test-clear-' . time() . '-' . rand(1000, 9999);

    $this->rateLimiter->attempt($key);
    $this->rateLimiter->attempt($key);
    expect($this->rateLimiter->remaining($key))->toBe(0);

    $this->rateLimiter->clear($key);
    expect($this->rateLimiter->remaining($key))->toBe(2); // Should reset
});
