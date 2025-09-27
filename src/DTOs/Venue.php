<?php

namespace Mbsoft\SemanticScholar\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class Venue extends Data
{
    public function __construct(
        public ?string $venueId = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $url = null,
        #[MapInputName('externalIds')]
        public ?array $externalIds = null,
        public ?array $issn = null,
        public ?string $publisher = null,
        public ?array $alternateNames = null,
        public ?string $abbreviation = null,
    ) {}

    public static function fromArray(array $item)
    {
        return new self(
            venueId: $item['venueId'] ?? null,
            name: $item['name'] ?? null,
            type: $item['type'] ?? null,
            url: $item['url'] ?? null,
            externalIds: $item['externalIds'] ?? null,
            issn: $item['issn'] ?? null,
            publisher: $item['publisher'] ?? null,
            alternateNames: $item['alternateNames'] ?? null,
            abbreviation: $item['abbreviation'] ?? null,
        );
    }

    /**
     * Get the ISSN identifier.
     */
    public function getIssn(): ?array
    {
        return $this->issn;
    }

    /**
     * Get the DBLP identifier.
     */
    public function getDblp(): ?string
    {
        return $this->externalIds['DBLP'] ?? null;
    }

    /**
     * Get all alternate names.
     */
    public function getAlternateNames(): array
    {
        return $this->alternateNames ?? [];
    }

    /**
     * Check if venue has a specific alternate name.
     */
    public function hasAlternateName(string $name): bool
    {
        return in_array($name, $this->getAlternateNames(), true);
    }

    /**
     * Get venue type classification.
     */
    public function getVenueType(): string
    {
        return $this->type ?? 'unknown';
    }

    /**
     * Check if venue is a journal.
     */
    public function isJournal(): bool
    {
        return strtolower($this->getVenueType()) === 'journal';
    }

    /**
     * Check if venue is a conference.
     */
    public function isConference(): bool
    {
        return strtolower($this->getVenueType()) === 'conference';
    }

    /**
     * Get the display name with fallback.
     */
    public function getDisplayName(): string
    {
        return $this->name ?? 'Unknown Venue';
    }

    /**
     * Get the abbreviation or short name.
     */
    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    /**
     * Convert to array with additional computed fields.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'issn' => $this->getIssn(),
            'dblp' => $this->getDblp(),
            'alternate_names' => $this->getAlternateNames(),
            'venue_type' => $this->getVenueType(),
            'is_journal' => $this->isJournal(),
            'is_conference' => $this->isConference(),
            'display_name' => $this->getDisplayName(),
        ]);
    }
}
