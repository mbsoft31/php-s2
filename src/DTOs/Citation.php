<?php

namespace Mbsoft\SemanticScholar\DTOs;

use Spatie\LaravelData\Data;

class Citation extends Data
{
    public function __construct(
        public string $paperId,
        public ?string $title = null,
        public ?int $year = null,
        public ?array $authors = null,
        public ?string $venue = null,
        public ?int $citationCount = null,
        public ?bool $isInfluential = null,
        public ?array $contexts = null,
        public ?array $intents = null,
    ) {}

    /**
     * Get author names from the citation.
     */
    public function getAuthorNames(): array
    {
        if (!$this->authors) {
            return [];
        }

        return array_map(fn($author) => $author['name'] ?? 'Unknown Author', $this->authors);
    }

    /**
     * Get citation contexts.
     */
    public function getContexts(): array
    {
        return $this->contexts ?? [];
    }

    /**
     * Get citation intents (background, methodology, result).
     */
    public function getIntents(): array
    {
        return $this->intents ?? [];
    }

    /**
     * Check if citation is influential.
     */
    public function isInfluential(): bool
    {
        return $this->isInfluential ?? false;
    }

    /**
     * Check if citation has methodology intent.
     */
    public function hasMethodologyIntent(): bool
    {
        return in_array('methodology', $this->getIntents());
    }

    /**
     * Check if citation has background intent.
     */
    public function hasBackgroundIntent(): bool
    {
        return in_array('background', $this->getIntents());
    }

    /**
     * Check if citation has result intent.
     */
    public function hasResultIntent(): bool
    {
        return in_array('result', $this->getIntents());
    }

    /**
     * Get the Semantic Scholar URL for this citation.
     */
    public function getSemanticScholarUrl(): string
    {
        return "https://www.semanticscholar.org/paper/{$this->paperId}";
    }

    /**
     * Convert to array with additional computed fields.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'author_names' => $this->getAuthorNames(),
            'contexts' => $this->getContexts(),
            'intents' => $this->getIntents(),
            'is_influential' => $this->isInfluential(),
            'has_methodology_intent' => $this->hasMethodologyIntent(),
            'has_background_intent' => $this->hasBackgroundIntent(),
            'has_result_intent' => $this->hasResultIntent(),
            'semantic_scholar_url' => $this->getSemanticScholarUrl(),
        ]);
    }
}
