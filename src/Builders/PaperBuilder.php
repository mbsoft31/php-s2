<?php

namespace Mbsoft\SemanticScholar\Builders;

use Mbsoft\SemanticScholar\Builder;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Illuminate\Support\Collection;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class PaperBuilder extends Builder
{
    protected string $endpoint = 'paper';

    /**
     * Find paper by DOI
     * @throws SemanticScholarException
     */
    public function findByDoi(string $doi): ?Paper
    {
        $this->endpoint = 'paper/DOI:' . $doi;

        $response = $this->makeRequest();

        if (!$response) {
            return null;
        }

        return new Paper($response);
    }

    /**
     * Find paper by ArXiv ID
     */
    public function findByArxiv(string $arxivId): ?Paper
    {
        $this->endpoint = 'paper/ARXIV:' . $arxivId;

        $response = $this->makeRequest();

        if (!$response) {
            return null;
        }

        return new Paper($response);
    }

    /**
     * Find paper by PubMed ID
     */
    public function findByPubmed(string $pubmedId): ?Paper
    {
        $this->endpoint = 'paper/PMID:' . $pubmedId;

        $response = $this->makeRequest();

        if (!$response) {
            return null;
        }

        return new Paper($response);
    }

    /**
     * Search papers
     */
    public function search(string $query): self
    {
        $this->endpoint = 'paper/search';
        $this->params['query'] = $query;

        return $this;
    }

    /**
     * Override get() to return Paper DTOs
     */
    public function get(): Collection
    {
        $response = $this->makeRequest();

        $papers = [];
        $data = $response['data'] ?? $response;

        if (!is_array($data)) {
            return collect([]);
        }

        foreach ($data as $paperData) {
            $papers[] = new Paper($paperData);
        }

        return collect($papers);
    }

    /**
     * Get first paper
     */
    public function first(): ?Paper
    {
        $this->limit(1);
        $papers = $this->get();

        return $papers->first();
    }

    /**
     * Semantic Scholar specific filters
     */
    public function byYear(int $year): self
    {
        $this->params['year'] = $year;
        return $this;
    }

    public function minCitations(int $count): self
    {
        $this->params['minCitationCount'] = $count;
        return $this;
    }

    public function openAccess(bool $openAccess = true): self
    {
        $this->params['openAccessPdf'] = $openAccess ? 'true' : 'false';
        return $this;
    }

    public function byFieldOfStudy(string $field): self
    {
        $this->params['fieldsOfStudy'] = $field;
        return $this;
    }
}
