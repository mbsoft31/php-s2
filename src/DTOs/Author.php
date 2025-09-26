<?php

namespace Mbsoft\SemanticScholar\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class Author extends Data
{
    public function __construct(
        public string $authorId,
        public string $name,
        #[MapInputName('externalIds')]
        public ?array $externalIds = null,
        public ?string $url = null,
        public ?int $paperCount = null,
        public ?int $citationCount = null,
        public ?int $hIndex = null,
        /** @var Paper[]|Optional */
        public array|Optional $papers = [],
        public ?array $affiliations = null,
        public ?string $homepage = null,
        /** @var array|Optional Recent papers if requested */
        public array|Optional $recentPapers = [],
        /** @var array|Optional Representative papers if requested */
        public array|Optional $representativePapers = [],
        public ?array $aliases = null,
        #[MapInputName('paperCountByYear')]
        public ?array $paperCountByYear = null,
    ) {}

    /**
     * Get the ORCID identifier.
     */
    public function getOrcid(): ?string
    {
        return $this->externalIds['ORCID'] ?? null;
    }

    /**
     * Get the DBLP identifier.
     */
    public function getDblp(): ?string
    {
        return $this->externalIds['DBLP'] ?? null;
    }

    /**
     * Get the Google Scholar identifier.
     */
    public function getGoogleScholarId(): ?string
    {
        return $this->externalIds['GoogleScholar'] ?? null;
    }

    /**
     * Get Semantic Scholar URL.
     */
    public function getSemanticScholarUrl(): string
    {
        return "https://www.semanticscholar.org/author/{$this->authorId}";
    }

    /**
     * Get the current affiliation.
     */
    public function getCurrentAffiliation(): ?string
    {
        return !empty($this->affiliations) ? $this->affiliations[0] : null;
    }

    /**
     * Get all affiliations.
     */
    public function getAllAffiliations(): array
    {
        return $this->affiliations ?? [];
    }

    /**
     * Check if author has a specific affiliation.
     */
    public function hasAffiliation(string $affiliation): bool
    {
        $affiliations = $this->getAllAffiliations();

        return in_array($affiliation, $affiliations, true);
    }

    /**
     * Get all aliases.
     */
    public function getAliases(): array
    {
        return $this->aliases ?? [];
    }

    /**
     * Check if author has a specific alias.
     */
    public function hasAlias(string $alias): bool
    {
        return in_array($alias, $this->getAliases(), true);
    }

    /**
     * Get paper count with fallback.
     */
    public function getPaperCount(): int
    {
        return $this->paperCount ?? 0;
    }

    /**
     * Get citation count with fallback.
     */
    public function getCitationCount(): int
    {
        return $this->citationCount ?? 0;
    }

    /**
     * Get H-index with fallback.
     */
    public function getHIndex(): int
    {
        return $this->hIndex ?? 0;
    }

    /**
     * Calculate average citations per paper.
     */
    public function getAverageCitationsPerPaper(): float
    {
        $paperCount = $this->getPaperCount();
        if ($paperCount === 0) {
            return 0.0;
        }

        return round($this->getCitationCount() / $paperCount, 2);
    }

    /**
     * Check if author is productive (10+ papers).
     */
    public function isProductive(): bool
    {
        return $this->getPaperCount() >= 10;
    }

    /**
     * Check if author is highly cited (1000+ citations).
     */
    public function isHighlyCited(): bool
    {
        return $this->getCitationCount() >= 1000;
    }

    /**
     * Check if author is influential (H-index >= 20).
     */
    public function isInfluential(): bool
    {
        return $this->getHIndex() >= 20;
    }

    /**
     * Get productivity level classification.
     */
    public function getProductivityLevel(): string
    {
        $paperCount = $this->getPaperCount();

        return match (true) {
            $paperCount >= 100 => 'highly_productive',
            $paperCount >= 50 => 'very_productive',
            $paperCount >= 20 => 'productive',
            $paperCount >= 10 => 'moderately_productive',
            $paperCount >= 5 => 'emerging',
            default => 'beginner'
        };
    }

    /**
     * Get impact level classification.
     */
    public function getImpactLevel(): string
    {
        $hIndex = $this->getHIndex();

        return match (true) {
            $hIndex >= 50 => 'exceptional',
            $hIndex >= 30 => 'high',
            $hIndex >= 20 => 'significant',
            $hIndex >= 10 => 'moderate',
            $hIndex >= 5 => 'emerging',
            default => 'early_career'
        };
    }

    /**
     * Get recent papers (last N years).
     */
    public function getRecentPapers(int $years = 5): array
    {
        if (!($this->papers instanceof Optional) && !empty($this->papers)) {
            $cutoffYear = date('Y') - $years;

            return array_filter($this->papers, function ($paper) use ($cutoffYear) {
                return ($paper->year ?? 0) >= $cutoffYear;
            });
        }

        return $this->recentPapers instanceof Optional ? [] : $this->recentPapers;
    }

    /**
     * Get most cited paper.
     */
    public function getMostCitedPaper(): ?Paper
    {
        if ($this->papers instanceof Optional || empty($this->papers)) {
            return null;
        }

        $mostCited = null;
        $maxCitations = 0;

        foreach ($this->papers as $paper) {
            if (($paper->citationCount ?? 0) > $maxCitations) {
                $maxCitations = $paper->citationCount;
                $mostCited = $paper;
            }
        }

        return $mostCited;
    }

    /**
     * Get career span information.
     */
    public function getCareerSpan(): ?array
    {
        if ($this->papers instanceof Optional || empty($this->papers)) {
            return null;
        }

        $years = array_filter(array_map(fn ($paper) => $paper->year, $this->papers));

        if (empty($years)) {
            return null;
        }

        $minYear = min($years);
        $maxYear = max($years);

        return [
            'start_year' => $minYear,
            'end_year' => $maxYear,
            'span_years' => $maxYear - $minYear + 1,
            'is_active' => $maxYear >= (date('Y') - 2),
        ];
    }

    /**
     * Get paper count by year.
     */
    public function getPaperCountByYear(): array
    {
        if ($this->paperCountByYear) {
            return $this->paperCountByYear;
        }

        if ($this->papers instanceof Optional || empty($this->papers)) {
            return [];
        }

        $yearCounts = [];
        foreach ($this->papers as $paper) {
            if ($paper->year) {
                $yearCounts[$paper->year] = ($yearCounts[$paper->year] ?? 0) + 1;
            }
        }

        ksort($yearCounts);

        return $yearCounts;
    }

    /**
     * Get peak productivity year.
     */
    public function getPeakProductivityYear(): ?int
    {
        $yearCounts = $this->getPaperCountByYear();

        if (empty($yearCounts)) {
            return null;
        }

        return array_key_first(array_slice(array_flip(array_reverse($yearCounts, true)), 0, 1, true));
    }

    /**
     * Get productivity trend over recent years.
     */
    public function getProductivityTrend(int $years = 5): string
    {
        $yearCounts = $this->getPaperCountByYear();
        $recentYears = array_slice($yearCounts, -$years, null, true);

        if (count($recentYears) < 2) {
            return 'insufficient_data';
        }

        $values = array_values($recentYears);
        $firstHalf = array_slice($values, 0, ceil(count($values) / 2));
        $secondHalf = array_slice($values, floor(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0) {
            return 'increasing';
        }

        $percentageChange = ($secondAvg - $firstAvg) / $firstAvg * 100;

        return match (true) {
            $percentageChange > 20 => 'increasing',
            $percentageChange > 5 => 'slightly_increasing',
            $percentageChange > -5 => 'stable',
            $percentageChange > -20 => 'slightly_decreasing',
            default => 'decreasing'
        };
    }

    /**
     * Get collaboration network size.
     */
    public function getCollaborationNetworkSize(): int
    {
        if ($this->papers instanceof Optional || empty($this->papers)) {
            return 0;
        }

        $collaborators = [];
        foreach ($this->papers as $paper) {
            if (!($paper->authors instanceof Optional)) {
                foreach ($paper->authors as $author) {
                    if ($author->authorId !== $this->authorId) {
                        $collaborators[$author->authorId] = true;
                    }
                }
            }
        }

        return count($collaborators);
    }

    /**
     * Get research impact score.
     */
    public function getResearchImpactScore(): float
    {
        $hIndex = $this->getHIndex();
        $citationCount = $this->getCitationCount();
        $paperCount = $this->getPaperCount();

        if ($paperCount === 0) {
            return 0.0;
        }

        // Weighted formula: H-index * 2 + avg citations per paper + log(total citations)
        $avgCitations = $citationCount / $paperCount;
        $logCitations = $citationCount > 0 ? log($citationCount) : 0;

        $score = ($hIndex * 2) + $avgCitations + $logCitations;

        return round($score, 2);
    }

    /**
     * Check if author is senior researcher.
     */
    public function isSeniorResearcher(): bool
    {
        $careerSpan = $this->getCareerSpan();
        if (!$careerSpan) {
            return false;
        }

        return $careerSpan['span_years'] >= 10 && $this->getHIndex() >= 15;
    }

    /**
     * Check if author is emerging researcher.
     */
    public function isEmergingResearcher(): bool
    {
        $careerSpan = $this->getCareerSpan();
        if (!$careerSpan) {
            return true;
        }

        return $careerSpan['span_years'] <= 5 && $this->getPaperCount() >= 3;
    }

    /**
     * Convert to array with additional computed fields.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'orcid' => $this->getOrcid(),
            'dblp' => $this->getDblp(),
            'google_scholar_id' => $this->getGoogleScholarId(),
            'semantic_scholar_url' => $this->getSemanticScholarUrl(),
            'current_affiliation' => $this->getCurrentAffiliation(),
            'all_affiliations' => $this->getAllAffiliations(),
            'average_citations_per_paper' => $this->getAverageCitationsPerPaper(),
            'is_productive' => $this->isProductive(),
            'is_highly_cited' => $this->isHighlyCited(),
            'is_influential' => $this->isInfluential(),
            'productivity_level' => $this->getProductivityLevel(),
            'impact_level' => $this->getImpactLevel(),
            'career_span' => $this->getCareerSpan(),
            'peak_productivity_year' => $this->getPeakProductivityYear(),
            'productivity_trend' => $this->getProductivityTrend(),
            'collaboration_network_size' => $this->getCollaborationNetworkSize(),
            'research_impact_score' => $this->getResearchImpactScore(),
            'is_senior_researcher' => $this->isSeniorResearcher(),
            'is_emerging_researcher' => $this->isEmergingResearcher(),
        ]);
    }
}
