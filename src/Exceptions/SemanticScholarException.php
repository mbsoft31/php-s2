<?php
// src/Exceptions/SemanticScholarException.php

namespace Mbsoft\SemanticScholar\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class SemanticScholarException extends Exception
{
    protected $response;
    protected $errorCode;

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

    public static function notFound(string $identifier): self
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
}
