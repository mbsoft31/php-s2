<?php

namespace Mbsoft\SemanticScholar\Builders;

use Mbsoft\SemanticScholar\Builder;
use Mbsoft\SemanticScholar\DTOs\Author;
use Illuminate\Support\Collection;

class AuthorBuilder extends Builder
{
    protected string $endpoint = 'author';

    /**
     * Find author by ORCID
     */
    public function findByOrcid(string $orcid): ?Author
    {
        $this->endpoint = 'author/ORCID:' . $orcid;

        $response = $this->makeRequest();

        if (!$response) {
            return null;
        }

        return new Author($response);
    }

    /**
     * Search authors
     */
    public function search(string $query): self
    {
        $this->endpoint = 'author/search';
        $this->params['query'] = $query;

        return $this;
    }

    /**
     * Override get() to return Author DTOs
     */
    public function get(): Collection
    {
        $response = $this->makeRequest();

        $authors = [];
        $data = $response['data'] ?? $response;

        if (!is_array($data)) {
            return collect([]);
        }

        foreach ($data as $authorData) {
            $authors[] = new Author($authorData);
        }

        return collect($authors);
    }

    /**
     * Get first author
     */
    public function first(): ?Author
    {
        $this->limit(1);
        $authors = $this->get();

        return $authors->first();
    }
}
