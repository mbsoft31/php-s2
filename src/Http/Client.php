<?php
// src/Http/Client.php - Fixed response handling

namespace Mbsoft\SemanticScholar\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class Client
{
    protected array $config;
    protected ?RateLimiter $rateLimiter = null;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->timeout = $config['timeout'] ?? 30;
        $this->retryAttempts = $config['retry_attempts'] ?? 3;
        $this->retryDelay = $config['retry_delay'] ?? 1000;

        // Only create RateLimiter if not in testing and enabled
        if (!app()->environment('testing') && ($config['rate_limiting']['enabled'] ?? true)) {
            $this->rateLimiter = new RateLimiter(
                $config['rate_limiting'] ?? [],
                !empty($config['api_key'])
            );
        }
    }

    public function get(string $endpoint, array $params = [], array $headers = []): array
    {
        // Check rate limit only if RateLimiter exists
        if ($this->rateLimiter && !$this->rateLimiter->attempt('api_request')) {
            throw SemanticScholarException::rateLimitExceeded();
        }

        try {
            $response = $this->makeHttpRequest('GET', $endpoint, $params, $headers);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            if ($e instanceof SemanticScholarException) {
                throw $e;
            }
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function retry(int $attempts): self
    {
        $this->retryAttempts = $attempts;
        return $this;
    }

    public function healthCheck(): bool
    {
        try {
            $response = $this->get('paper/DOI:10.1093/mind/lix.236.433');
            return !empty($response);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Make HTTP request with proper error handling
     */
    protected function makeHttpRequest(string $method, string $endpoint, array $params = [], array $headers = []): Response
    {
        $url = $this->buildUrl($endpoint);
        $defaultHeaders = $this->getDefaultHeaders();
        $allHeaders = array_merge($defaultHeaders, $headers);

        $http = Http::timeout($this->timeout)
            ->withHeaders($allHeaders)
            ->retry($this->retryAttempts, $this->retryDelay);

        // Add logging in development
        if (config('semantic-scholar.logging.log_requests', false)) {
            Log::info('Semantic Scholar API Request', [
                'method' => $method,
                'url' => $url,
                'params' => $params,
                'headers' => $allHeaders,
            ]);
        }

        $response = match(strtoupper($method)) {
            'GET' => $http->get($url, $params),
            'POST' => $http->post($url, $params),
            'PUT' => $http->put($url, $params),
            'DELETE' => $http->delete($url, $params),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: $method")
        };

        // Handle HTTP errors
        if ($response->failed()) {
            $this->handleHttpError($response);
        }

        return $response;
    }

    /**
     * Parse HTTP response - CRITICAL FIX for test compatibility
     */
    protected function parseResponse(Response $response): array
    {
        try {
            // Get response body
            $body = $response->body();

            // Handle empty responses
            if (empty($body)) {
                return [];
            }

            // Try to decode JSON
            $decoded = $response->json();

            // Handle null response (invalid JSON)
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            // Ensure we return an array
            if (!is_array($decoded)) {
                throw new \Exception('Response is not an array');
            }

            // Log response in development
            if (config('semantic-scholar.logging.log_responses', false)) {
                Log::info('Semantic Scholar API Response', [
                    'status' => $response->status(),
                    'data' => $decoded,
                ]);
            }

            return $decoded;

        } catch (\Exception $e) {
            // More specific error for debugging
            throw new \Exception("Invalid response format: {$e->getMessage()}. Response body: " . substr($response->body(), 0, 200));
        }
    }

    /**
     * Handle HTTP errors
     */
    protected function handleHttpError(Response $response): void
    {
        $status = $response->status();
        $body = $response->json() ?? [];
        $message = $body['error'] ?? $body['message'] ?? 'HTTP Error';

        match($status) {
            400 => throw SemanticScholarException::invalidRequest($message),
            401 => throw SemanticScholarException::unauthorized(),
            404 => throw SemanticScholarException::notFound($message),
            429 => throw SemanticScholarException::rateLimitExceeded(),
            default => throw SemanticScholarException::networkError("HTTP {$status}: {$message}")
        };
    }

    /**
     * Build complete URL
     */
    protected function buildUrl(string $endpoint): string
    {
        $baseUrl = $this->config['base_url'] ?? 'https://api.semanticscholar.org/graph/v1';
        return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Get default headers
     */
    protected function getDefaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-SemanticScholar/1.0',
        ];

        if (!empty($this->config['api_key'])) {
            $headers['x-api-key'] = $this->config['api_key'];
        }

        return $headers;
    }
}
