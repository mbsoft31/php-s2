<?php

namespace Mbsoft\SemanticScholar\Builders;

use Mbsoft\SemanticScholar\Builder;
use Mbsoft\SemanticScholar\DTOs\Author;
use Illuminate\Support\Collection;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class AuthorBuilder extends Builder
{
    protected string $currentEndpoint = 'author';

    /**
     * Override get() to ensure Author DTOs
     * @throws SemanticScholarException
     */
    public function get(): Collection
    {
        $results = parent::get();

        // Ensure all items are Author DTOs
        return $results->map(function ($item) {
            return $item instanceof Author ? $item : Author::from((array) $item);
        });
    }

    /**
     * Get first author
     */
    public function first(): ?Author
    {
        $author = parent::first();
        return $author instanceof Author ? $author : ($author ? Author::from((array) $author) : null);
    }

    /**
     * Find author by ORCID with Author DTO guarantee
     * @throws SemanticScholarException
     */
    public function findByOrcid(string $orcid): ?Author
    {
        $author = parent::findByOrcid($orcid);
        return $author instanceof Author ? $author : null;
    }

    /**
     * Search authors with automatic endpoint setting
     */
    public function search(string $query): self
    {
        $this->currentEndpoint = 'author/search';
        return parent::search($query);
    }
}
