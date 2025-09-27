<?php
// src/Http/RateLimiter.php - Complete Fixed Version

namespace Mbsoft\SemanticScholar\Http;

use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    protected array $config;
    protected bool $hasApiKey;
    protected int $requestsPerSecond;
    protected int $requestsPerMinute;

    public function __construct(array $config, bool $hasApiKey = false)
    {
        $this->config = $config;
        $this->hasApiKey = $hasApiKey;
        $this->requestsPerSecond = $hasApiKey
            ? ($config['with_api_key_rps'] ?? 100)
            : ($config['requests_per_second'] ?? 1);
        $this->requestsPerMinute = $this->requestsPerSecond * 60;
    }

    public function attempt(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= $this->requestsPerSecond) {
            return false;
        }

        // Increment attempts using simple increment with TTL
        Cache::put($cacheKey, $attempts + 1, now()->addSeconds(1));

        return true;
    }

    public function retryAfter(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts < $this->requestsPerSecond) {
            return 0;
        }

        // Return milliseconds until next second
        return (1000 - (now()->millisecond));
    }

    public function remaining(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = Cache::get($cacheKey, 0);

        return max(0, $this->requestsPerSecond - $attempts);
    }

    protected function getCacheKey(string $key): string
    {
        // Use second-based cache keys to auto-expire
        return 'rate_limit_' . $key . '_' . now()->format('Y-m-d_H:i:s');
    }

    public function clear(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::forget($cacheKey);
    }

    public function waitIfNeeded()
    {
        $key = 'global'; // Use a global key for simplicity
        while (!$this->attempt($key)) {
            $delay = $this->retryAfter($key);
            usleep($delay * 1000); // Convert ms to µs
        }
    }
}
