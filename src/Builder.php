<?php

namespace Mbsoft\SemanticScholar;

use BadMethodCallException;
use DateInterval;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Mbsoft\SemanticScholar\DTOs\Author;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Mbsoft\SemanticScholar\DTOs\Venue;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;
use Mbsoft\SemanticScholar\Http\Client;

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

    protected Client $client;
    protected array $params = [];
    protected array $headers = [];
    protected ?string $endpoint = null;
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
     * Execute the query and get results
     * @throws SemanticScholarException
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
     * @throws SemanticScholarException
     */
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();

        return $results->first();
    }

    /**
     * Find by specific ID (Paper, Author, etc.)
     * @throws Exception
     */
    public function find(string $id): ?object
    {
        $this->endpoint = $this->endpoint . '/' . $id;

        try {
            $response = $this->makeRequest();

            if (empty($response)) {
                return null;
            }

            return $this->mapSingleItem($response);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Make HTTP request through production Client
     * @throws SemanticScholarException
     */
    protected function makeRequest(): array
    {
        $url = $this->buildUrl();
        $params = $this->buildQueryParams();

        return $this->client
            ->timeout($this->timeout)
            ->retry($this->retryAttempts)
            ->get($url, $params);
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
        } elseif (isset($response[0])) {
            // Direct array of items
            $rawItems = $response;
        } else {
            // Single item
            return collect([$this->mapSingleItem($response)]);
        }

        foreach ($rawItems as $item) {
            $mappedItem = $this->mapSingleItem($item);
            if ($mappedItem) {
                $items[] = $mappedItem;
            }
        }

        return collect($items);
    }

    /**
     * Map single API response item to appropriate DTO
     */
    protected function mapSingleItem(array $item): ?object
    {
        // Determine entity type and map to appropriate DTO
        if ($this->isPaperResponse($item)) {
            return Paper::from($item);
        } elseif ($this->isAuthorResponse($item)) {
            return Author::from($item);
        } elseif ($this->isVenueResponse($item)) {
            return Venue::from($item);
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
            str_contains($this->endpoint, 'paper');
    }

    /**
     * Detect if response is an Author
     */
    protected function isAuthorResponse(array $item): bool
    {
        return isset($item['authorId']) ||
            (isset($item['name']) && !isset($item['paperId'])) ||
            str_contains($this->endpoint, 'author');
    }

    /**
     * Detect if response is a Venue
     */
    protected function isVenueResponse(array $item): bool
    {
        return isset($item['venueId']) ||
            str_contains($this->endpoint, 'venue');
    }

    /**
     * Build the API URL for the request.
     */
    private function buildUrl(?string $id = null): string
    {
        $baseUrl = config('semantic-scholar.base_url', 'https://api.semanticscholar.org/graph/v1');

        if (!is_null($id)) {
            return "$baseUrl/$this->endpoint/$id";
        }

        // Handle different endpoint patterns
        return match ($this->endpoint) {
            'papers' => $this->searchQuery ? " $baseUrl/paper/search" : "$baseUrl/paper/batch",
            'authors' => "$baseUrl/author/batch",
            'venues' => "$baseUrl/venue/batch",
            'recommendations' => "$baseUrl/recommendations/v1/papers",
            default => "$baseUrl/$this->endpoint",
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

        if (!empty($this->fields)) {
            $params['fields'] = implode(',', $this->fields);
        } elseif ($defaultFields = config("semantic-scholar.default_fields.$this->entity")) {
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
     * Generate a cache key for the request.
     */
    private function getCacheKey(string $url, array $params = []): string
    {
        $key = $url . '?' . http_build_query($params);

        return config('semantic-scholar.cache.prefix', 'semantic_scholar') . '_' . md5($key);
    }

    /**
     * Execute a callback with caching if enabled.
     */
    private function executeCacheable(string $cacheKey, callable $callback): mixed
    {
        if ($this->cacheTtl === null && !$this->cacheForever) {
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
    protected function cache(): \Illuminate\Contracts\Cache\Repository
    {
        $store = config('semantic-scholar.cache.store', 'default');

        return CacheFacade::store($store === 'default' ? null : $store);
    }


    /**
     * Map API response data to DTO.
     */
    private function mapToDto(array $data): object
    {
        $dtoClass = 'Mbsoft\\SemanticScholar\\DTOs\\' . ucfirst(rtrim($this->entity, 's'));

        if (class_exists($dtoClass)) {
            return $dtoClass::from($data);
        }

        return (object)$data;
    }

    // Semantic Scholar specific methods

    /**
     * Get paginated results.
     * @throws SemanticScholarException
     */
    public function paginate(int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $offset = ($page - 1) * $perPage;
        $params = array_merge($this->buildQueryParams(), [
            'limit' => $perPage,
            'offset' => $offset,
        ]);

        $url = $this->buildUrl();
        $response = $this->makeRequest();

        $items = collect($response['data'] ?? [])->map(fn($item) => $this->mapToDto($item));
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

                $response = $this->makeRequest();
                $results = $response['data'] ?? [];

                foreach ($results as $result) {
                    yield $this->mapToDto($result);
                }

                // Handle pagination - Semantic Scholar uses 'next' token
                $currentToken = $response['next'] ?? null;
                $currentOffset += $limit;
            } while (!empty($results) && ($currentToken || count($results) === $limit));
        });
    }

    /**
     * Filter papers by publication year.
     */
    public function byYear(int $year): self
    {
        return $this->where('year', (string)$year);
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
     * Filter papers by minimum citation count.
     */
    public function minCitations(int $count): self
    {
        return $this->where('minCitationCount', (string)$count);
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
     * Find a paper by DOI.
     * @throws Exception
     */
    public function findByDoi(string $doi): ?object
    {
        if ($this->endpoint !== 'papers') {
            throw new SemanticScholarException('DOI lookup is only available for papers');
        }

        // Ensure DOI has proper format
        $doi = str_starts_with($doi, 'DOI:') ? $doi : "DOI:{$doi}";

        return $this->find($doi);
    }

    /**
     * Find a paper by ArXiv ID.
     * @throws SemanticScholarException
     * @throws Exception
     */
    public function findByArxiv(string $arxivId): ?object
    {
        if ($this->endpoint !== 'papers') {
            throw new SemanticScholarException('ArXiv lookup is only available for papers');
        }

        $arxivId = str_starts_with($arxivId, 'ARXIV:') ? $arxivId : "ARXIV:{$arxivId}";

        return $this->find($arxivId);
    }

    /**
     * Find a paper by PubMed ID.
     * @throws Exception
     */
    public function findByPubmed(string $pubmedId): ?object
    {
        if ($this->entity !== 'papers') {
            throw new SemanticScholarException('PubMed lookup is only available for papers');
        }

        $pubmedId = str_starts_with($pubmedId, 'PMID:') ? $pubmedId : "PMID:$pubmedId";

        return $this->find($pubmedId);
    }

    /**
     * Find an author by ORCID.
     * @throws Exception
     */
    public function findByOrcid(string $orcid): ?object
    {
        if ($this->endpoint !== 'authors') {
            throw new SemanticScholarException('ORCID lookup is only available for authors');
        }

        $orcid = str_starts_with($orcid, 'ORCID:') ? $orcid : "ORCID:$orcid";

        return $this->find($orcid);
    }

    /**
     * Dump query information for debugging.
     */
    public function dump(): array
    {
        return [
            'entity' => $this->endpoint,
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
     * Get the URL for this query (for debugging).
     */
    public function toUrl(): string
    {
        $url = $this->buildUrl();
        $params = $this->buildQueryParams();

        if (empty($params)) {
            return $url;
        }

        return $url . '?' . http_build_query($params);
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

        throw new BadMethodCallException("Method $name does not exist.");
    }

}
