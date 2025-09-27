<?php
// src/SemanticScholar.php - Complete updated version

namespace Mbsoft\SemanticScholar;

use Exception;
use Illuminate\Support\Collection;
use Mbsoft\SemanticScholar\DTOs\Author;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;
use Mbsoft\SemanticScholar\Http\Client;

class SemanticScholar
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new paper builder
     */
    public function papers(): Builder
    {
        return app(Builder::class)->endpoint('paper');
    }

    /**
     * Create a new author builder
     */
    public function authors(): Builder
    {
        return app(Builder::class)->endpoint('author');
    }

    /**
     * Create a new venue builder
     */
    public function venues(): Builder
    {
        return app(Builder::class)->endpoint('venue');
    }

    /**
     * Quick search papers by query
     * @throws SemanticScholarException
     */
    public function searchPapers(string $query, int $limit = 10): Collection
    {
        return $this->papers()->search($query)->limit($limit)->get();
    }

    /**
     * Quick search authors by query
     * @throws SemanticScholarException
     */
    public function searchAuthors(string $query, int $limit = 10): Collection
    {
        return $this->authors()->search($query)->limit($limit)->get();
    }

    /**
     * Find paper by DOI
     * @throws SemanticScholarException
     */
    public function findPaperByDoi(string $doi): ?Paper
    {
        return $this->papers()->findByDoi($doi);
    }

    /**
     * Find paper by ArXiv ID
     * @throws SemanticScholarException
     */
    public function findPaperByArxiv(string $arxivId): ?Paper
    {
        return $this->papers()->findByArxiv($arxivId);
    }

    /**
     * Find author by ORCID
     * @throws SemanticScholarException
     */
    public function findAuthorByOrcid(string $orcid): ?Author
    {
        return $this->authors()->findByOrcid($orcid);
    }

    /**
     * Get HTTP client instance
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Test API connection
     */
    public function test(): bool
    {
        try {
            $paper = $this->findPaperByDoi('10.1093/mind/lix.236.433');
            return $paper !== null;
        } catch (Exception $e) {
            // TODO: Log the exception if needed
            return false;
        }
    }

    /**
     * Check API health
     */
    public function healthCheck(): bool
    {
        return $this->client->healthCheck();
    }

    /**
     * Get API usage statistics (if available)
     */
    public function getUsageStats(): array
    {
        // This would be implemented based on Semantic Scholar's rate limit headers
        return [
            'requests_made' => 0,
            'requests_remaining' => 0,
            'reset_time' => null,
        ];
    }
}
