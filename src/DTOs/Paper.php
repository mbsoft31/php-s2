<?php

namespace Mbsoft\SemanticScholar\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class Paper extends Data
{
    public function __construct(
        public string $paperId,
        public ?string $title = null,
        public ?int $year = null,
        #[MapInputName('abstract')]
        public ?string $abstract = null,
        public ?int $citationCount = null,
        public ?int $influentialCitationCount = null,
        public ?int $referenceCount = null,
        public ?string $corpusId = null,
        /** @var Author[]|Optional */
        public array|Optional $authors = [],
        /** @var Citation[]|Optional */
        public array|Optional $citations = [],
        /** @var Citation[]|Optional */
        public array|Optional $references = [],
        public ?Venue $venue = null,
        #[MapInputName('openAccessPdf')]
        public ?array $openAccessPdf = null,
        #[MapInputName('externalIds')]
        public ?array $externalIds = null,
        public ?string $url = null,
        #[MapInputName('publicationTypes')]
        public ?array $publicationTypes = null,
        #[MapInputName('publicationDate')]
        public ?string $publicationDate = null,
        #[MapInputName('fieldsOfStudy')]
        public ?array $fieldsOfStudy = null,
        public ?array $s2FieldsOfStudy = null,
        public ?array $publicationVenue = null,
        public ?string $journal = null,
        #[MapInputName('isOpenAccess')]
        public ?bool $isOpenAccess = null,
        public ?array $tldr = null,
        /** @var array|Optional Embedding vector if requested */
        public array|Optional $embedding = [],
    ) {}

    /**
     * Get the DOI identifier.
     */
    public function getDoi(): ?string
    {
        return $this->externalIds['DOI'] ?? null;
    }

    /**
     * Get the ArXiv identifier.
     */
    public function getArxivId(): ?string
    {
        return $this->externalIds['ArXiv'] ?? null;
    }

    /**
     * Get the PubMed identifier.
     */
    public function getPubmedId(): ?string
    {
        return $this->externalIds['PubMed'] ?? null;
    }

    /**
     * Get the Microsoft Academic Graph identifier.
     */
    public function getMagId(): ?string
    {
        return $this->externalIds['MAG'] ?? null;
    }

    /**
     * Get the DBLP identifier.
     */
    public function getDblpId(): ?string
    {
        return $this->externalIds['DBLP'] ?? null;
    }

    /**
     * Get the ACL identifier.
     */
    public function getAclId(): ?string
    {
        return $this->externalIds['ACL'] ?? null;
    }

    /**
     * Check if paper has open access.
     */
    public function hasOpenAccess(): bool
    {
        return !empty($this->openAccessPdf['url']) || $this->isOpenAccess === true;
    }

    /**
     * Get the open access PDF URL.
     */
    public function getOpenAccessUrl(): ?string
    {
        return $this->openAccessPdf['url'] ?? null;
    }

    /**
     * Get the open access status.
     */
    public function getOpenAccessStatus(): ?string
    {
        return $this->openAccessPdf['status'] ?? null;
    }

    /**
     * Get the TL;DR summary.
     */
    public function getTldr(): ?string
    {
        if (isset($this->tldr['text'])) {
            return $this->tldr['text'];
        }

        // Fallback to truncated abstract
        if ($this->abstract) {
            return strlen($this->abstract) > 200
                ? substr($this->abstract, 0, 200) . '...'
                : $this->abstract;
        }

        return null;
    }

    /**
     * Get all author names.
     */
    public function getAuthorNames(): array
    {
        if ($this->authors instanceof Optional) {
            return [];
        }

        return array_map(fn ($author) => $author->name ?? 'Unknown Author', $this->authors);
    }

    /**
     * Get the first author.
     */
    public function getFirstAuthor(): ?Author
    {
        if ($this->authors instanceof Optional || empty($this->authors)) {
            return null;
        }

        return $this->authors[0] ?? null;
    }

    /**
     * Get the last author.
     */
    public function getLastAuthor(): ?Author
    {
        if ($this->authors instanceof Optional || empty($this->authors)) {
            return null;
        }

        return end($this->authors) ?: null;
    }

    /**
     * Get the venue name.
     */
    public function getVenueName(): ?string
    {
        return $this->venue?->name
            ?? $this->publicationVenue['name']
            ?? $this->journal
            ?? null;
    }

    /**
     * Get all fields of study.
     */
    public function getFieldsOfStudy(): array
    {
        return array_merge(
            $this->fieldsOfStudy ?? [],
            array_column($this->s2FieldsOfStudy ?? [], 'category')
        );
    }

    /**
     * Get the primary field of study.
     */
    public function getPrimaryFieldOfStudy(): ?string
    {
        $fields = $this->getFieldsOfStudy();

        return !empty($fields) ? $fields[0] : null;
    }

    /**
     * Get the influential citation ratio.
     */
    public function getInfluentialCitationRatio(): ?float
    {
        if ($this->citationCount === null || $this->influentialCitationCount === null) {
            return null;
        }

        if ($this->citationCount === 0) {
            return 0.0;
        }

        return round($this->influentialCitationCount / $this->citationCount, 4);
    }

    /**
     * Check if paper is highly influential.
     */
    public function isHighlyInfluential(): bool
    {
        $ratio = $this->getInfluentialCitationRatio();

        return $ratio !== null && $ratio >= 0.1; // 10% or higher
    }

    /**
     * Check if paper is highly cited.
     */
    public function isHighlyCited(): bool
    {
        return ($this->citationCount ?? 0) >= 100;
    }

    /**
     * Check if paper is recent.
     */
    public function isRecent(): bool
    {
        return $this->year && $this->year >= (date('Y') - 2);
    }

    /**
     * Get the age of the paper in years.
     */
    public function getAgeInYears(): ?int
    {
        return $this->year ? (date('Y') - $this->year) : null;
    }

    /**
     * Get citation velocity (citations per year).
     */
    public function getCitationVelocity(): ?float
    {
        $age = $this->getAgeInYears();
        if (!$age || $age === 0 || !$this->citationCount) {
            return null;
        }

        return round($this->citationCount / $age, 2);
    }

    /**
     * Get impact score based on citations and influence.
     */
    public function getImpactScore(): ?float
    {
        if (!$this->citationCount || !$this->influentialCitationCount) {
            return null;
        }

        // Weighted score: total citations + 2x influential citations
        $score = $this->citationCount + (2 * $this->influentialCitationCount);

        // Normalize by age if available
        if ($age = $this->getAgeInYears()) {
            $score = $score / max($age, 1);
        }

        return round($score, 2);
    }

    /**
     * Generate BibTeX citation.
     */
    public function toBibTeX(): string
    {
        $authors = implode(' and ', $this->getAuthorNames());
        $year = $this->year ?? 'Unknown';
        $title = $this->title ?? 'Unknown Title';
        $venue = $this->getVenueName() ?? 'Unknown Venue';
        $doi = $this->getDoi();

        // Generate citation key
        $firstAuthor = $this->getFirstAuthor();
        $firstAuthorLastName = 'Unknown';
        if ($firstAuthor && $firstAuthor->name) {
            $nameParts = explode(' ', $firstAuthor->name);
            $firstAuthorLastName = end($nameParts);
        }

        $citationKey = $firstAuthorLastName . $year;

        $bibtex = "@article{{$citationKey},\n";
        $bibtex .= "  author = {{$authors}},\n";
        $bibtex .= "  title = {{{$title}}},\n";
        $bibtex .= "  journal = {{$venue}},\n";
        $bibtex .= "  year = {{$year}}";

        if ($doi) {
            $bibtex .= ",\n  doi = {{$doi}}";
        }

        if ($this->url) {
            $bibtex .= ",\n  url = {{$this->url}}";
        }

        if ($this->hasOpenAccess() && ($pdfUrl = $this->getOpenAccessUrl())) {
            $bibtex .= ",\n  note = {Open Access PDF: {$pdfUrl}}";
        }

        $bibtex .= "\n}";

        return $bibtex;
    }

    /**
     * Generate APA citation.
     */
    public function toApa(): string
    {
        $authors = $this->getAuthorNames();
        $authorsString = '';

        if (count($authors) === 1) {
            $authorsString = $authors[0];
        } elseif (count($authors) === 2) {
            $authorsString = implode(' & ', $authors);
        } elseif (count($authors) <= 7) {
            $authorsString = implode(', ', array_slice($authors, 0, -1)) . ', & ' . end($authors);
        } else {
            $authorsString = implode(', ', array_slice($authors, 0, 6)) . ', ... ' . end($authors);
        }

        $year = $this->year ? "({$this->year})" : '';
        $title = $this->title ?? 'Unknown title';
        $venue = $this->getVenueName() ?? 'Unknown venue';

        $citation = "{$authorsString}. {$year}. {$title}. {$venue}";

        if ($doi = $this->getDoi()) {
            $citation .= ". https://doi.org/{$doi}";
        }

        return $citation;
    }

    /**
     * Generate MLA citation.
     */
    public function toMla(): string
    {
        $authors = $this->getAuthorNames();
        $authorsString = '';

        if (count($authors) === 1) {
            $authorsString = $authors[0];
        } elseif (count($authors) === 2) {
            $authorsString = $authors[0] . ', and ' . $authors[1];
        } else {
            $authorsString = $authors[0] . ', et al';
        }

        $title = "\"" . ($this->title ?? 'Unknown title') . "\"";
        $venue = $this->getVenueName() ?? 'Unknown venue';
        $year = $this->year ?? 'n.d.';

        return "{$authorsString}. {$title} {$venue}, {$year}.";
    }

    /**
     * Get Semantic Scholar URL.
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
            'doi' => $this->getDoi(),
            'arxiv_id' => $this->getArxivId(),
            'pubmed_id' => $this->getPubmedId(),
            'has_open_access' => $this->hasOpenAccess(),
            'open_access_url' => $this->getOpenAccessUrl(),
            'tldr' => $this->getTldr(),
            'author_names' => $this->getAuthorNames(),
            'venue_name' => $this->getVenueName(),
            'fields_of_study' => $this->getFieldsOfStudy(),
            'is_highly_cited' => $this->isHighlyCited(),
            'is_highly_influential' => $this->isHighlyInfluential(),
            'citation_velocity' => $this->getCitationVelocity(),
            'impact_score' => $this->getImpactScore(),
            'semantic_scholar_url' => $this->getSemanticScholarUrl(),
        ]);
    }
}
