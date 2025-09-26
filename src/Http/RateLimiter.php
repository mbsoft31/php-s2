<?php

namespace Mbsoft\SemanticScholar\Http;

use Illuminate\Support\Facades\Cache;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class RateLimiter
{
    private int $requestsPerSecond;

    private int $burstLimit;

    private string $cachePrefix;

    public function __construct()
    {
        $this->requestsPerSecond = config('semantic-scholar.rate_limit.requests_per_second', 1);
        $this->burstLimit = config('semantic-scholar.rate_limit.burst_limit', 10);
        $this->cachePrefix = config('semantic-scholar.cache.prefix', 'semantic_scholar') . '_rate_limit';
    }

    /**
     * Check if request can proceed under rate limits.
     */
    public function attempt(string $key = 'default'): bool
    {
        $windowKey = $this->getWindowKey($key);
        $burstKey = $this->getBurstKey($key);

        // Check burst limit first
        if (!$this->checkBurstLimit($burstKey)) {
            return false;
        }

        // Check requests per second limit
        if (!$this->checkPerSecondLimit($windowKey)) {
            return false;
        }

        // Record the request
        $this->recordRequest($windowKey, $burstKey);

        return true;
    }

    /**
     * Wait for the next available slot if rate limited.
     */
    public function waitIfNeeded(string $key = 'default'): void
    {
        $attempts = 0;
        $maxAttempts = 10;

        while (!$this->attempt($key) && $attempts < $maxAttempts) {
            $sleepTime = $this->calculateSleepTime();
            usleep($sleepTime * 1000); // Convert to microseconds
            $attempts++;
        }

        if ($attempts >= $maxAttempts) {
            throw SemanticScholarException::rateLimitExceeded();
        }
    }

    /**
     * Get time to wait before next request.
     */
    public function getRetryAfter(string $key = 'default'): int
    {
        $windowKey = $this->getWindowKey($key);
        $burstKey = $this->getBurstKey($key);

        $windowCount = Cache::get($windowKey, 0);
        $burstCount = Cache::get($burstKey, 0);

        if ($burstCount >= $this->burstLimit) {
            return 60; // Wait 1 minute if burst limit exceeded
        }

        if ($windowCount >= $this->requestsPerSecond) {
            return 1; // Wait 1 second if per-second limit exceeded
        }

        return 0;
    }

    /**
     * Clear rate limit for a key.
     */
    public function clear(string $key = 'default'): void
    {
        Cache::forget($this->getWindowKey($key));
        Cache::forget($this->getBurstKey($key));
    }

    /**
     * Get current usage stats.
     */
    public function getStats(string $key = 'default'): array
    {
        $windowKey = $this->getWindowKey($key);
        $burstKey = $this->getBurstKey($key);

        return [
            'current_window_requests' => Cache::get($windowKey, 0),
            'current_burst_requests' => Cache::get($burstKey, 0),
            'requests_per_second_limit' => $this->requestsPerSecond,
            'burst_limit' => $this->burstLimit,
            'retry_after' => $this->getRetryAfter($key),
        ];
    }

    /**
     * Check burst limit (requests per minute).
     */
    private function checkBurstLimit(string $burstKey): bool
    {
        $burstCount = Cache::get($burstKey, 0);

        return $burstCount < $this->burstLimit;
    }

    /**
     * Check per-second limit.
     */
    private function checkPerSecondLimit(string $windowKey): bool
    {
        $windowCount = Cache::get($windowKey, 0);

        return $windowCount < $this->requestsPerSecond;
    }

    /**
     * Record a request in both windows.
     */
    private function recordRequest(string $windowKey, string $burstKey): void
    {
        // Increment per-second counter
        Cache::increment($windowKey);
        Cache::expire($windowKey, 1); // Expire after 1 second

        // Increment burst counter
        Cache::increment($burstKey);
        Cache::expire($burstKey, 60); // Expire after 1 minute
    }

    /**
     * Calculate sleep time based on current load.
     */
    private function calculateSleepTime(): int
    {
        // Base sleep time in milliseconds
        $baseSleep = 1000 / $this->requestsPerSecond;

        // Add jitter to prevent thundering herd
        $jitter = rand(0, 100);

        return (int) ($baseSleep + $jitter);
    }

    /**
     * Get cache key for per-second window.
     */
    private function getWindowKey(string $key): string
    {
        $timestamp = time();

        return "{$this->cachePrefix}:window:{$key}:{$timestamp}";
    }

    /**
     * Get cache key for burst window.
     */
    private function getBurstKey(string $key): string
    {
        $minute = floor(time() / 60);

        return "{$this->cachePrefix}:burst:{$key}:{$minute}";
    }
}
