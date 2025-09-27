<?php
// src/Exceptions/SemanticScholarException.php

namespace Mbsoft\SemanticScholar\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class SemanticScholarException extends Exception
{
    protected ?ResponseInterface $response;
    protected int $errorCode;

    public function __construct(string $message, int $code = 0, ResponseInterface $response = null)
    {
        parent::__construct($message, $code);
        $this->response = $response;
        $this->errorCode = $code;
    }

    public static function rateLimitExceeded(): self
    {
        return new self('Rate limit exceeded. Consider using an API key for higher limits.', 429);
    }

    public static function unauthorized(): self
    {
        return new self('Unauthorized. Please check your API key.', 401);
    }

    public static function notFound(string $identifier = 'nan'): self
    {
        return new self("Resource not found: {$identifier}", 404);
    }

    public static function invalidRequest(string $details): self
    {
        return new self("Invalid request: {$details}", 400);
    }

    public static function networkError(string $details): self
    {
        return new self("Network error: {$details}", 0);
    }

    public static function badRequest(mixed $message): SemanticScholarException
    {
        return new self(is_string($message) ? $message : 'Bad request', 400);
    }

    public static function serverError(mixed $message): SemanticScholarException
    {
        return new self(is_string($message) ? $message : 'Server error', 500);
    }

    public static function connectionError(string $string): SemanticScholarException
    {
        return new self("Connection error: {$string}", 0);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function isRateLimit(): bool
    {
        return $this->errorCode === 429;
    }

    public function isUnauthorized(): bool
    {
        return $this->errorCode === 401;
    }

    public function isNotFound(): bool
    {
        return $this->errorCode === 404;
    }

    // isRetryable checks if the error is retryable (e.g., network issues, 5xx errors, rate limiting)
    public function isRetryable(): bool
    {
        return in_array($this->errorCode, [0, 429, 500, 502, 503, 504], true);
    }
}
