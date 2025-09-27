<?php

namespace Mbsoft\SemanticScholar\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class Client
{
    private RateLimiter $rateLimiter;

    private array $config;

    public function __construct(RateLimiter $rateLimiter = null)
    {
        $this->rateLimiter = $rateLimiter ?? new RateLimiter();
        $this->config = config('semantic-scholar', []);
    }

    /**
     * Make a GET request to the Semantic Scholar API.
     * @throws SemanticScholarException
     */
    public function get(string $url, array $params = []): array
    {
        return $this->makeRequest('GET', $url, $params);
    }

    /**
     * Make a POST request to the Semantic Scholar API.
     * @throws SemanticScholarException
     */
    public function post(string $url, array $data = []): array
    {
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Make a request with comprehensive error handling and retry logic.
     * @throws SemanticScholarException
     */
    private function makeRequest(string $method, string $url, array $data = []): array
    {
        $this->rateLimiter->waitIfNeeded();

        $attempts = 0;
        $maxAttempts = $this->config['retry']['attempts'] ?? 3;
        $delay = $this->config['retry']['delay'] ?? 100;

        while ($attempts < $maxAttempts) {
            try {
                $this->logRequest($method, $url, $data);

                $response = $this->buildHttpClient()->{strtolower($method)}($url, $data);

                $this->logResponse($response);

                return $this->handleResponse($response);
            } catch (ConnectionException $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw SemanticScholarException::connectionError("Connection failed after $maxAttempts attempts: {$e->getMessage()}");
                }
                $this->sleep($delay * $attempts);
            } catch (SemanticScholarException $e) {
                if (!$e->isRetryable() || $attempts >= $maxAttempts - 1) {
                    throw $e;
                }
                $attempts++;
                $this->sleep($delay * $attempts);
            }
        }

        throw SemanticScholarException::serverError('Maximum retry attempts exceeded');
    }

    /**
     * Handle the HTTP response and extract data.
     * @throws SemanticScholarException
     */
    private function handleResponse(Response $response): array
    {
        if ($response->status() === 404) {

            throw SemanticScholarException::notFound();
        }

        if ($response->status() === 401) {
            throw SemanticScholarException::unauthorized();
        }

        if ($response->status() === 429) {
            // $retryAfter = $response->header('Retry-After');
            throw SemanticScholarException::rateLimitExceeded();
        }

        if ($response->status() === 400) {
            $message = $response->json('message') ?? 'Bad request';
            throw SemanticScholarException::badRequest($message);
        }

        if ($response->status() >= 500) {
            $message = $response->json('message') ?? 'Server error';
            throw SemanticScholarException::serverError($message);
        }

        if ($response->failed()) {
            $message = $response->json('message') ?? 'Request failed';
            throw new SemanticScholarException($message, $response->status());
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new SemanticScholarException('Invalid response format');
        }

        return $data;
    }

    /**
     * Build the HTTP client with appropriate headers and configuration.
     */
    private function buildHttpClient(): PendingRequest
    {
        $client = Http::acceptJson()
            ->timeout($this->config['timeout'] ?? 30)
            ->withHeaders([
                'User-Agent' => $this->config['user_agent'] ?? 'Laravel-SemanticScholar/1.0',
            ]);

        // Add API key if configured
        if ($apiKey = $this->config['api_key']) {
            $client->withHeaders(['x-api-key' => $apiKey]);
        }

        // Add debug logging if enabled
        if ($this->config['debug']['log_requests'] ?? false) {
            $client->beforeSending(function ($request) {
                $this->logDebugRequest($request);
            });
        }

        return $client;
    }

    /**
     * Sleep for the specified number of milliseconds.
     */
    private function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }

    /**
     * Log the request for monitoring.
     */
    private function logRequest(string $method, string $url, array $data): void
    {
        if (!($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        $level = $this->config['logging']['level'] ?? 'info';
        $channel = $this->config['logging']['channel'] ?? 'default';

        Log::channel($channel)->{$level}('Semantic Scholar API Request', [
            'method' => $method,
            'url' => $url,
            'params_count' => count($data),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log the response for monitoring.
     */
    private function logResponse(Response $response): void
    {
        if (!($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        $level = $this->config['logging']['level'] ?? 'info';
        $channel = $this->config['logging']['channel'] ?? 'default';

        $logData = [
            'status' => $response->status(),
            'size' => strlen($response->body()),
            'timestamp' => now()->toISOString(),
        ];

        if ($response->failed()) {
            $level = 'error';
            $logData['error'] = $response->json('message') ?? 'Unknown error';
        }

        Log::channel($channel)->{$level}('Semantic Scholar API Response', $logData);
    }

    /**
     * Log debug information about the request.
     */
    private function logDebugRequest($request): void
    {
        if (!($this->config['debug']['log_requests'] ?? false)) {
            return;
        }

        Log::debug('Semantic Scholar Debug Request', [
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => $request->headers(),
        ]);
    }

    public function timeout(mixed $timeout): static
    {
        $this->config['timeout'] = $timeout;
        return $this;
    }

    public function retry(mixed $retryAttempts): static
    {
        $this->config['retry']['attempts'] = $retryAttempts;
        return $this;
    }
}
