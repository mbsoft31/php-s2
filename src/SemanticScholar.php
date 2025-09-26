<?php

namespace Mbsoft\SemanticScholar;

class SemanticScholar
{
    /**
     * Create a new papers query builder.
     */
    public function papers(): Builder
    {
        return new Builder('papers');
    }

    /**
     * Create a new authors query builder.
     */
    public function authors(): Builder
    {
        return new Builder('authors');
    }

    /**
     * Create a new venues query builder.
     */
    public function venues(): Builder
    {
        return new Builder('venues');
    }

    /**
     * Create a new recommendations query builder.
     */
    public function recommendations(): Builder
    {
        return new Builder('recommendations');
    }

    /**
     * Get the current API configuration.
     */
    public function config(): array
    {
        return config('semantic-scholar', []);
    }

    /**
     * Get the API base URL.
     */
    public function getBaseUrl(): string
    {
        return config('semantic-scholar.base_url', 'https://api.semanticscholar.org/graph/v1');
    }

    /**
     * Check if API key is configured.
     */
    public function hasApiKey(): bool
    {
        return ! empty(config('semantic-scholar.api_key'));
    }

    /**
     * Get the configured rate limit.
     */
    public function getRateLimit(): array
    {
        return config('semantic-scholar.rate_limit', [
            'requests_per_second' => 1,
            'burst_limit' => 10,
        ]);
    }
}
