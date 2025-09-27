<?php

namespace Mbsoft\SemanticScholar;

use Mbsoft\SemanticScholar\Http\Client;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Mbsoft\SemanticScholar\DTOs\Author;
use Mbsoft\SemanticScholar\DTOs\Venue;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class Builder
{
    protected Client $client;
    protected array $params = [];
    protected array $headers = [];
    protected string $currentEndpoint = '';
    protected ?int $cacheFor = null;
    protected int $retryAttempts = 3;
    protected int $timeout = 30;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->timeout = config('semantic-scholar.timeout', 30);
        $this->retryAttempts = config('semantic-scholar.retry_attempts', 3);
    }

    /**
     * Set the API endpoint
     */
    public function endpoint(string $endpoint): self
    {
        $this->currentEndpoint = $endpoint;
        return $this;
    }

    /**
     * Search for papers or authors
     */
    public function search(string $query): self
    {
        $this->params['query'] = $query;

        if (empty($this->currentEndpoint)) {
            $this->currentEndpoint = 'paper/search';
        } elseif (!str_contains($this->currentEndpoint, 'search')) {
            $this->currentEndpoint .= '/search';
        }

        return $this;
    }

    /**
     * Set the fields to return
     */
    public function fields(string $fields): self
    {
        $this->params['fields'] = $fields;
        return $this;
    }

    /**
     * Set the limit
     */
    public function limit(int $limit): self
    {
        $this->params['limit'] = $limit;
        return $this;
    }

    /**
     * Set the offset
     */
    public function offset(int $offset): self
    {
        $this->params['offset'] = $offset;
        return $this;
    }

    /**
     * Filter by year
     */
    public function byYear(int $year): self
    {
        $this->params['year'] = $year;
        return $this;
    }

    /**
     * Filter by minimum citations
     */
    public function minCitations(int $count): self
    {
        $this->params['minCitationCount'] = $count;
        return $this;
    }

    /**
     * Filter by maximum citations
     */
    public function maxCitations(int $count): self
    {
        $this->params['maxCitationCount'] = $count;
        return $this;
    }

    /**
     * Filter by open access
     */
    public function openAccess(bool $openAccess = true): self
    {
        $this->params['openAccessPdf'] = $openAccess ? 'true' : 'false';
        return $this;
    }

    /**
     * Filter by field of study
     */
    public function byFieldOfStudy(string $field): self
    {
        $this->params['fieldsOfStudy'] = $field;
        return $this;
    }

    /**
     * Filter by publication type
     */
    public function publicationType(string $type): self
    {
        $this->params['publicationType'] = $type;
        return $this;
    }

    /**
     * Filter by venue
     */
    public function byVenue(string $venue): self
    {
        $this->params['venue'] = $venue;
        return $this;
    }

    /**
     * Only influential citations
     */
    public function influentialCitations(bool $only = true): self
    {
        $this->params['influentialCitations'] = $only ? 'true' : 'false';
        return $this;
    }

    /**
     * Cache the results
     */
    public function cache(int $ttl = null): self
    {
        $this->cacheFor = $ttl ?? config('semantic-scholar.cache.default_ttl', 3600);
        return $this;
    }

    /**
     * Set retry attempts
     */
    public function retry(int $attempts): self
    {
        $this->retryAttempts = $attempts;
        return $this;
    }

    /**
     * Set timeout
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Find by specific ID
     */
    public function find(string $id): ?object
    {
        $endpoint = rtrim($this->currentEndpoint, '/search') . '/' . $id;

        try {
            $response = $this->client
                ->timeout($this->timeout)
                ->retry($this->retryAttempts)
                ->get($endpoint, [], $this->headers);

            if (!$response || empty($response)) {
                return null;
            }

            return $this->mapToDTO($response);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    /**
     * Find paper by DOI
     */
    public function findByDoi(string $doi): ?object
    {
        $endpoint = 'paper/DOI:' . $doi;

        try {
            $response = $this->client
                ->timeout($this->timeout)
                ->retry($this->retryAttempts)
                ->get($endpoint, $this->buildParams(), $this->headers);

            return $response ? new Paper($response) : null;

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    /**
     * Find paper by ArXiv ID
     */
    public function findByArxiv(string $arxivId): ?object
    {
        $endpoint = 'paper/ARXIV:' . $arxivId;

        try {
            $response = $this->client
                ->timeout($this->timeout)
                ->retry($this->retryAttempts)
                ->get($endpoint, $this->buildParams(), $this->headers);

            return $response ? new Paper($response) : null;

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    /**
     * Find paper by PubMed ID
     */
    public function findByPubmed(string $pubmedId): ?object
    {
        $endpoint = 'paper/PMID:' . $pubmedId;

        try {
            $response = $this->client
                ->timeout($this->timeout)
                ->retry($this->retryAttempts)
                ->get($endpoint, $this->buildParams(), $this->headers);

            return $response ? new Paper($response) : null;

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    /**
     * Find author by ORCID
     */
    public function findByOrcid(string $orcid): ?object
    {
        $endpoint = 'author/ORCID:' . $orcid;

        try {
            $response = $this->client
                ->timeout($this->timeout)
                ->retry($this->retryAttempts)
                ->get($endpoint, $this->buildParams(), $this->headers);

            return $response ? new Author($response) : null;

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    /**
     * Execute the query and get results
     */
    public function get(): Collection
    {
        try {
            $response = $this->makeRequest();
            return $this->parseResponse($response);

        } catch (\Exception $e) {
            throw SemanticScholarException::networkError($e->getMessage());
        }
    }

    /**
     * Get first result
     */
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();

        return $results->first();
    }

    /**
     * Get results as cursor (memory efficient)
     */
    public function cursor(): LazyCollection
    {
        return LazyCollection::make(function () {
            $offset = $this->params['offset'] ?? 0;
            $limit = $this->params['limit'] ?? 100;

            do {
                $this->params['offset'] = $offset;
                $this->params['limit'] = $limit;

                $response = $this->makeRequest();
                $items = $this->parseResponse($response);

                foreach ($items as $item) {
                    yield $item;
                }

                $offset += $limit;
                $hasMore = $items->count() === $limit;

            } while ($hasMore);
        });
    }

    /**
     * Process results in chunks
     */
    public function chunk(int $size, callable $callback): void
    {
        $this->cursor()->chunk($size)->each($callback);
    }

    /**
     * Batch process multiple IDs
     */
    public function batch(array $ids, int $batchSize = 100): Collection
    {
        $results = collect();

        foreach (array_chunk($ids, $batchSize) as $batch) {
            foreach ($batch as $id) {
                $result = $this->find($id);
                if ($result) {
                    $results->push($result);
                }
            }
        }

        return $results;
    }

    /**
     * Make HTTP request through production Client
     */
    protected function makeRequest(): array
    {
        $endpoint = $this->currentEndpoint;
        $params = $this->buildParams();

        return $this->client
            ->timeout($this->timeout)
            ->retry($this->retryAttempts)
            ->get($endpoint, $params, $this->headers);
    }

    /**
     * Parse API response and map to DTOs
     */
    protected function parseResponse(array $response): Collection
    {
        $items = [];

        // Handle different response formats
        if (isset($response['data'])) {
            // Search results format
            $rawItems = $response['data'];
        } elseif (isset($response['results'])) {
            // Alternative results format
            $rawItems = $response['results'];
        } elseif (is_array($response) && isset($response[0])) {
            // Direct array of items
            $rawItems = $response;
        } else {
            // Single item
            return collect([$this->mapToDTO($response)]);
        }

        foreach ($rawItems as $item) {
            if (is_array($item)) {
                $mappedItem = $this->mapToDTO($item);
                if ($mappedItem) {
                    $items[] = $mappedItem;
                }
            }
        }

        return collect($items);
    }

    /**
     * Map single API response item to appropriate DTO
     */
    protected function mapToDTO(array $item): ?object
    {
        // Determine entity type and map to appropriate DTO
        if ($this->isPaperResponse($item)) {
            return new Paper($item);
        } elseif ($this->isAuthorResponse($item)) {
            return new Author($item);
        } elseif ($this->isVenueResponse($item)) {
            return new Venue($item);
        }

        // Fallback for unknown response types
        return (object) $item;
    }

    /**
     * Detect if response is a Paper
     */
    protected function isPaperResponse(array $item): bool
    {
        return isset($item['paperId']) ||
            isset($item['title']) ||
            str_contains($this->currentEndpoint, 'paper');
    }

    /**
     * Detect if response is an Author
     */
    protected function isAuthorResponse(array $item): bool
    {
        return isset($item['authorId']) ||
            (isset($item['name']) && !isset($item['paperId'])) ||
            str_contains($this->currentEndpoint, 'author');
    }

    /**
     * Detect if response is a Venue
     */
    protected function isVenueResponse(array $item): bool
    {
        return isset($item['venueId']) ||
            str_contains($this->currentEndpoint, 'venue');
    }

    /**
     * Build query parameters
     */
    protected function buildParams(): array
    {
        $params = $this->params;

        // Set default fields if not specified
        if (!isset($params['fields'])) {
            $params['fields'] = $this->getDefaultFields();
        }

        return array_filter($params, fn($value) => $value !== null && $value !== '');
    }

    /**
     * Get default fields based on endpoint
     */
    protected function getDefaultFields(): string
    {
        if (str_contains($this->currentEndpoint, 'paper')) {
            return config('semantic-scholar.defaults.fields.paper',
                'paperId,title,abstract,authors,year,citationCount,influentialCitationCount,fieldsOfStudy,publicationDate,venue,openAccessPdf,tldr'
            );
        } elseif (str_contains($this->currentEndpoint, 'author')) {
            return config('semantic-scholar.defaults.fields.author',
                'authorId,name,affiliations,paperCount,citationCount,hIndex'
            );
        }

        return '';
    }

    /**
     * Build complete URL
     */
    protected function buildUrl(): string
    {
        $baseUrl = config('semantic-scholar.base_url');
        return rtrim($baseUrl, '/') . '/' . ltrim($this->currentEndpoint, '/');
    }
}
