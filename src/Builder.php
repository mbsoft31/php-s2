<?php

namespace Mbsoft\SemanticScholar;

use BadMethodCallException;
use DateInterval;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class Builder
{
    private array $filters = [];

    private ?string $searchQuery = null;

    private array $fields = [];

    private int $limit = 100;

    private int $offset = 0;

    private ?string $token = null; // For pagination

    private ?string $apiKey = null;

    private DateInterval|Carbon|int|null $cacheTtl = null;

    private bool $cacheForever = false;

    public function __construct(private string $entity)
    {
        $this->apiKey = config('semantic-scholar.api_key');
    }

    /**
     * Search for entities by text query.
     */
    public function search(string $query): self
    {
        $this->searchQuery = $query;

        return $this;
    }

    /**
     * Select specific fields to return.
     */
    public function fields(array|string $fields): self
    {
        $this->fields = is_array($fields) ? $fields : func_get_args();

        return $this;
    }

    /**
     * Add a filter condition.
     */
    public function where(string $field, string $value): self
    {
        $this->filters[$field] = $value;

        return $this;
    }

    /**
     * Limit the number of results.
     */
    public function limit(int $limit): self
    {
        $this->limit = min($limit, 1000); // API limit

        return $this;
    }

    /**
     * Set the offset for pagination.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Find a single entity by ID.
     */
    public function find(string $id): ?object
    {
        $url = $this->buildUrl($id);
        $params = $this->buildQueryParams();
        $cacheKey = $this->getCacheKey($url, $params);

        return $this->executeCacheable($cacheKey, function () use ($url, $params) {
            $response = $this->makeRequest($url, $params);

            return $response ? $this->mapToDto($response) : null;
        });
    }

    /**
     * Get a collection of results.
     */
    public function get(): Collection
    {
        $url = $this->buildUrl();
        $params = $this->buildQueryParams();
        $cacheKey = $this->getCacheKey($url, $params);

        return $this->executeCacheable($cacheKey, function () use ($url, $params) {
            $response = $this->makeRequest($url, $params);
            $results = $response['data'] ?? [];

            return collect($results)->map(fn ($item) => $this->mapToDto($item));
        });
    }

    /**
     * Get paginated results.
     */
    public function paginate(int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $offset = ($page - 1) * $perPage;
        $params = array_merge($this->buildQueryParams(), [
            'limit' => $perPage,
            'offset' => $offset,
        ]);

        $url = $this->buildUrl();
        $response = $this->makeRequest($url, $params);

        $items = collect($response['data'] ?? [])->map(fn ($item) => $this->mapToDto($item));
        $total = $response['total'] ?? 0;

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    /**
     * Get a memory-efficient cursor for large datasets.
     */
    public function cursor(): LazyCollection
    {
        return new LazyCollection(function () {
            $currentOffset = $this->offset;
            $currentToken = $this->token;
            $limit = min($this->limit, 1000);

            do {
                $params = $this->buildQueryParams();
                $params['limit'] = $limit;

                if ($currentToken) {
                    $params['token'] = $currentToken;
                } else {
                    $params['offset'] = $currentOffset;
                }

                $response = $this->makeRequest($this->buildUrl(), $params);
                $results = $response['data'] ?? [];

                foreach ($results as $result) {
                    yield $this->mapToDto($result);
                }

                // Handle pagination - Semantic Scholar uses 'next' token
                $currentToken = $response['next'] ?? null;
                $currentOffset += $limit;
            } while (! empty($results) && ($currentToken || count($results) === $limit));
        });
    }

    /**
     * Cache results for the specified duration.
     */
    public function cacheFor(DateInterval|int $ttl): self
    {
        $this->cacheTtl = is_int($ttl) ? now()->addSeconds($ttl) : $ttl;

        return $this;
    }

    /**
     * Cache results forever.
     */
    public function cacheForever(): self
    {
        $this->cacheForever = true;

        return $this;
    }

    /**
     * Disable caching for this query.
     */
    public function disableCache(): self
    {
        $this->cacheTtl = null;
        $this->cacheForever = false;

        return $this;
    }

    // Semantic Scholar specific methods

    /**
     * Filter papers by publication year.
     */
    public function byYear(int $year): self
    {
        return $this->where('year', (string) $year);
    }

    /**
     * Filter papers by year range.
     */
    public function byYearRange(int $startYear, int $endYear): self
    {
        return $this->where('year', "{$startYear}-{$endYear}");
    }

    /**
     * Filter papers by venue.
     */
    public function byVenue(string $venue): self
    {
        return $this->where('venue', $venue);
    }

    /**
     * Filter papers by minimum citation count.
     */
    public function minCitations(int $count): self
    {
        return $this->where('minCitationCount', (string) $count);
    }

    /**
     * Filter for open access papers only.
     */
    public function openAccess(bool $openAccess = true): self
    {
        return $this->where('openAccessPdf', $openAccess ? 'true' : 'false');
    }

    /**
     * Filter papers by field of study.
     */
    public function byFieldOfStudy(string $field): self
    {
        return $this->where('fieldsOfStudy', $field);
    }

    /**
     * Filter papers by publication type.
     */
    public function publicationType(string $type): self
    {
        return $this->where('publicationTypes', $type);
    }

    // Semantic Scholar specific find methods

    /**
     * Find a paper by DOI.
     */
    public function findByDoi(string $doi): ?object
    {
        if ($this->entity !== 'papers') {
            throw new SemanticScholarException('DOI lookup is only available for papers');
        }

        // Ensure DOI has proper format
        $doi = str_starts_with($doi, 'DOI:') ? $doi : "DOI:{$doi}";

        return $this->find($doi);
    }

    /**
     * Find a paper by ArXiv ID.
     */
    public function findByArxiv(string $arxivId): ?object
    {
        if ($this->entity !== 'papers') {
            throw new SemanticScholarException('ArXiv lookup is only available for papers');
        }

        $arxivId = str_starts_with($arxivId, 'ARXIV:') ? $arxivId : "ARXIV:{$arxivId}";

        return $this->find($arxivId);
    }

    /**
     * Find a paper by PubMed ID.
     */
    public function findByPubmed(string $pubmedId): ?object
    {
        if ($this->entity !== 'papers') {
            throw new SemanticScholarException('PubMed lookup is only available for papers');
        }

        $pubmedId = str_starts_with($pubmedId, 'PMID:') ? $pubmedId : "PMID:{$pubmedId}";

        return $this->find($pubmedId);
    }

    /**
     * Find a paper by Corpus ID.
     */
    public function findByCorpusId(string $corpusId): ?object
    {
        if ($this->entity !== 'papers') {
            throw new SemanticScholarException('Corpus ID lookup is only available for papers');
        }

        $corpusId = str_starts_with($corpusId, 'CorpusId:') ? $corpusId : "CorpusId:{$corpusId}";

        return $this->find($corpusId);
    }

    /**
     * Find an author by ORCID.
     */
    public function findByOrcid(string $orcid): ?object
    {
        if ($this->entity !== 'authors') {
            throw new SemanticScholarException('ORCID lookup is only available for authors');
        }

        $orcid = str_starts_with($orcid, 'ORCID:') ? $orcid : "ORCID:{$orcid}";

        return $this->find($orcid);
    }

    /**
     * Build the API URL for the request.
     */
    private function buildUrl(?string $id = null): string
    {
        $baseUrl = config('semantic-scholar.base_url', 'https://api.semanticscholar.org/graph/v1');

        if ($id) {
            return "{$baseUrl}/{$this->entity}/{$id}";
        }

        // Handle different endpoint patterns
        return match ($this->entity) {
            'papers' => $this->searchQuery ? "{$baseUrl}/paper/search" : "{$baseUrl}/paper/batch",
            'authors' => "{$baseUrl}/author/batch",
            'venues' => "{$baseUrl}/venue/batch",
            'recommendations' => "{$baseUrl}/recommendations/v1/papers",
            default => "{$baseUrl}/{$this->entity}",
        };
    }

    /**
     * Build query parameters for the request.
     */
    private function buildQueryParams(): array
    {
        $params = [];

        if ($this->searchQuery) {
            $params['query'] = $this->searchQuery;
        }

        if (! empty($this->fields)) {
            $params['fields'] = implode(',', $this->fields);
        } elseif ($defaultFields = config("semantic-scholar.default_fields.{$this->entity}")) {
            $params['fields'] = implode(',', $defaultFields);
        }

        if ($this->limit) {
            $params['limit'] = $this->limit;
        }

        if ($this->offset) {
            $params['offset'] = $this->offset;
        }

        if ($this->token) {
            $params['token'] = $this->token;
        }

        // Add filters
        foreach ($this->filters as $key => $value) {
            $params[$key] = $value;
        }

        return array_filter($params);
    }

    /**
     * Make an HTTP request to the API.
     */
    private function makeRequest(string $url, array $params = []): ?array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => config('semantic-scholar.user_agent', 'Laravel-Semantic-Scholar/1.0'),
        ];

        if ($this->apiKey) {
            $headers['x-api-key'] = $this->apiKey;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(config('semantic-scholar.timeout', 30))
                ->retry(
                    config('semantic-scholar.retry.attempts', 3),
                    config('semantic-scholar.retry.delay', 100)
                )
                ->get($url, $params);

            if ($response->status() === 404) {
                return null;
            }

            if ($response->status() === 429) {
                throw new SemanticScholarException(
                    'Rate limit exceeded. Please slow down your requests.',
                    429
                );
            }

            if ($response->failed()) {
                $errorMessage = $response->json('message') ?? 'API request failed';
                throw new SemanticScholarException($errorMessage, $response->status());
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw new SemanticScholarException("Connection failed: {$e->getMessage()}");
        } catch (\Exception $e) {
            if ($e instanceof SemanticScholarException) {
                throw $e;
            }
            throw new SemanticScholarException("Request failed: {$e->getMessage()}");
        }
    }

    /**
     * Map API response data to DTO.
     */
    private function mapToDto(array $data): object
    {
        $dtoClass = 'Mbsoft\\SemanticScholar\\DTOs\\'.ucfirst(rtrim($this->entity, 's'));

        if (class_exists($dtoClass)) {
            return $dtoClass::from($data);
        }

        return (object) $data;
    }

    /**
     * Generate a cache key for the request.
     */
    private function getCacheKey(string $url, array $params = []): string
    {
        $key = $url.'?'.http_build_query($params);

        return config('semantic-scholar.cache.prefix', 'semantic_scholar').'_'.md5($key);
    }

    /**
     * Execute a callback with caching if enabled.
     */
    private function executeCacheable(string $cacheKey, callable $callback): mixed
    {
        if ($this->cacheTtl === null && ! $this->cacheForever) {
            return $callback();
        }

        $cache = $this->cache();

        if ($this->cacheForever) {
            return $cache->rememberForever($cacheKey, $callback);
        }

        return $cache->remember($cacheKey, $this->cacheTtl, $callback);
    }

    /**
     * Get the cache repository instance.
     */
    protected function cache(): Repository
    {
        $store = config('semantic-scholar.cache.store', 'default');

        return CacheFacade::store($store === 'default' ? null : $store);
    }

    /**
     * Get the URL for this query (for debugging).
     */
    public function toUrl(): string
    {
        $url = $this->buildUrl();
        $params = $this->buildQueryParams();

        if (empty($params)) {
            return $url;
        }

        return $url.'?'.http_build_query($params);
    }

    /**
     * Dump query information for debugging.
     */
    public function dump(): array
    {
        return [
            'entity' => $this->entity,
            'url' => $this->toUrl(),
            'params' => $this->buildQueryParams(),
            'filters' => $this->filters,
            'search_query' => $this->searchQuery,
            'fields' => $this->fields,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * Handle dynamic "where" methods.
     */
    public function __call(string $name, array $arguments): self
    {
        if (str_starts_with($name, 'where')) {
            $filterKey = Str::snake(substr($name, 5));
            $value = $arguments[0];

            return $this->where($filterKey, $value);
        }

        throw new BadMethodCallException("Method {$name} does not exist.");
    }
}
