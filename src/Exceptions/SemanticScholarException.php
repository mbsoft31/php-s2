<?php

namespace Mbsoft\SemanticScholar\Exceptions;

use Exception;

class SemanticScholarException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception for rate limit errors.
     */
    public static function rateLimitExceeded(int $retryAfter = null): self
    {
        $message = 'Rate limit exceeded.';
        if ($retryAfter) {
            $message .= " Retry after {$retryAfter} seconds.";
        }

        return new self($message, 429);
    }

    /**
     * Create an exception for authentication errors.
     */
    public static function unauthorized(string $message = 'Authentication failed'): self
    {
        return new self($message, 401);
    }

    /**
     * Create an exception for not found errors.
     */
    public static function notFound(string $resource = 'Resource'): self
    {
        return new self("{$resource} not found.", 404);
    }

    /**
     * Create an exception for bad requests.
     */
    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self($message, 400);
    }

    /**
     * Create an exception for server errors.
     */
    public static function serverError(string $message = 'Internal server error'): self
    {
        return new self($message, 500);
    }

    /**
     * Create an exception for network/connection errors.
     */
    public static function connectionError(string $message = 'Connection failed'): self
    {
        return new self($message, 0);
    }

    /**
     * Create an exception for timeout errors.
     */
    public static function timeout(string $message = 'Request timeout'): self
    {
        return new self($message, 408);
    }

    /**
     * Create an exception for validation errors.
     */
    public static function validation(string $message = 'Validation failed'): self
    {
        return new self($message, 422);
    }

    /**
     * Check if this is a rate limit error.
     */
    public function isRateLimitError(): bool
    {
        return $this->getCode() === 429;
    }

    /**
     * Check if this is an authentication error.
     */
    public function isAuthenticationError(): bool
    {
        return $this->getCode() === 401;
    }

    /**
     * Check if this is a not found error.
     */
    public function isNotFoundError(): bool
    {
        return $this->getCode() === 404;
    }

    /**
     * Check if this is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->getCode() >= 400 && $this->getCode() < 500;
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->getCode() >= 500 && $this->getCode() < 600;
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return in_array($this->getCode(), [429, 500, 502, 503, 504]);
    }
}
