<?php
// src/Builders/PaperBuilder.php - New specialized builder

namespace Mbsoft\SemanticScholar\Builders;

use Mbsoft\SemanticScholar\Builder;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Illuminate\Support\Collection;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class PaperBuilder extends Builder
{
    protected string $currentEndpoint = 'paper';

    /**
     * Override get() to ensure Paper DTOs
     * @throws SemanticScholarException
     */
    public function get(): Collection
    {
        $results = parent::get();

        // Ensure all items are Paper DTOs
        return $results->map(function ($item) {
            return $item instanceof Paper ? $item : Paper::from((array) $item);
        });
    }

    /**
     * Get first paper
     */
    public function first(): ?Paper
    {
        $paper = parent::first();
        return $paper instanceof Paper ? $paper : ($paper ? Paper::from((array) $paper) : null);
    }

    /**
     * Find paper by DOI with Paper DTO guarantee
     * @throws SemanticScholarException
     */
    public function findByDoi(string $doi): ?Paper
    {
        $paper = parent::findByDoi($doi);
        return $paper instanceof Paper ? $paper : null;
    }

    /**
     * Find paper by ArXiv ID with Paper DTO guarantee
     * @throws SemanticScholarException
     */
    public function findByArxiv(string $arxivId): ?Paper
    {
        $paper = parent::findByArxiv($arxivId);
        return $paper instanceof Paper ? $paper : null;
    }

    /**
     * Find paper by PubMed ID with Paper DTO guarantee
     * @throws SemanticScholarException
     */
    public function findByPubmed(string $pubmedId): ?Paper
    {
        $paper = parent::findByPubmed($pubmedId);
        return $paper instanceof Paper ? $paper : null;
    }

    /**
     * Search papers with automatic endpoint setting
     */
    public function search(string $query): self
    {
        $this->currentEndpoint = 'paper/search';
        return parent::search($query);
    }
}
