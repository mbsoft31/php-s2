<?php

use Mbsoft\SemanticScholar\DTOs\Author;

beforeEach(function () {
    $this->authorData = [
        'authorId' => '1741101',
        'name' => 'Alan Turing',
        'affiliations' => ['University of Cambridge', 'Princeton University'],
        'paperCount' => 50,
        'citationCount' => 150000,
        'hIndex' => 35,
        'papers' => [
            ['paperId' => 'paper1', 'title' => 'Computing Machinery and Intelligence', 'year' => 1950, 'citationCount' => 10000],
            ['paperId' => 'paper2', 'title' => 'The Chemical Basis of Morphogenesis', 'year' => 1952, 'citationCount' => 8000],
            ['paperId' => 'paper3', 'title' => 'On Computable Numbers', 'year' => 1936, 'citationCount' => 12000],
        ]
    ];

    $this->author = Author::fromArray($this->authorData);
});

test('can get basic properties', function () {
    expect($this->author->getAuthorId())->toBe('1741101')
        ->and($this->author->getName())->toBe('Alan Turing')
        ->and($this->author->getPaperCount())->toBe(50)
        ->and($this->author->getCitationCount())->toBe(150000)
        ->and($this->author->getHIndex())->toBe(35);
});

test('can get affiliations', function () {
    $affiliations = collect($this->author->getAffiliations());

    expect($affiliations)->toHaveCount(2)
        ->and($affiliations->contains('University of Cambridge'))->toBeTrue()
        ->and($affiliations->contains('Princeton University'))->toBeTrue();
});

test('can calculate average citations per paper', function () {
    $average = $this->author->getAverageCitationsPerPaper();

    expect($average)->toBe(3000.0); // 150000 / 50 = 3000
});

test('can get career span', function () {
    $span = $this->author->getCareerSpan();

    expect($span['span_years'])->toBe(17); // 1952 - 1936 = 16
});

test('can get career start year', function () {
    $startYear = $this->author->getCareerStartYear();

    expect($startYear)->toBe(1936);
});

test('can get career end year', function () {
    $endYear = $this->author->getCareerEndYear();

    expect($endYear)->toBe(1952);
});

test('can analyze productivity trend', function () {
    $trend = $this->author->getPaperCountByYear();

    expect($trend)->toBeArray()
        ->and($trend)->toHaveKey('1936')
        ->and($trend)->toHaveKey('1950')
        ->and($trend)->toHaveKey('1952')
        ->and($trend['1936'])->toBe(1)
        ->and($trend['1950'])->toBe(1)
        ->and($trend['1952'])->toBe(1);
});

test('can get peak productivity year', function () {
    // Add more papers for one year to create a peak
    $authorWithPeak = Author::fromArray([
        'authorId' => '1741101',
        'name' => 'Alan Turing',
        'papers' => [
            ['paperId' => 'p_1950', 'year' => 1950, 'citationCount' => 1000],
            ['paperId' => 'p_1950', 'year' => 1950, 'citationCount' => 2000],
            ['paperId' => 'p_1950', 'year' => 1950, 'citationCount' => 3000],
            ['paperId' => 'p_1951', 'year' => 1951, 'citationCount' => 500],
            ['paperId' => 'p_1952', 'year' => 1952, 'citationCount' => 800],
        ]
    ]);

    $peakYear = $authorWithPeak->getPeakProductivityYear();

    expect($peakYear)->toBe(1950);
});

test('can calculate research impact score', function () {
    $impactScore = $this->author->getResearchImpactScore();

    expect($impactScore)->toBeFloat()
        ->and($impactScore)->toBeGreaterThan(0);
});

test('can classify impact level', function () {
    $impactLevel = $this->author->getImpactLevel();

    expect($impactLevel)->toBeIn(['exceptional', 'high', 'significant', 'moderate', 'emerging', 'early_career']);
});

test('can determine if active', function () {
    // Create author with recent papers
    $activeAuthor = Author::fromArray([
        'authorId' => 'active-123',
        'name' => 'Active Researcher',
        'papers' => [
            ['paperId' => 'p_' . now()->year - 1, 'year' => now()->year - 1],
            ['paperId' => 'p_' . now()->year, 'year' => now()->year],
        ]
    ]);

    expect($activeAuthor->isActive())->toBeTrue()
        ->and($this->author->isActive())->toBeFalse(); // Papers from 1950s
});

test('can calculate collaboration network size', function () {
    $networkSize = $this->author->getCollaborationNetworkSize();

    expect($networkSize)->toBeInt()
        ->and($networkSize)->toBeGreaterThanOrEqual(0);
});

test('can get most cited paper', function () {
    $mostCited = $this->author->getMostCitedPaper();

    expect($mostCited->title)->toBe('On Computable Numbers')
        ->and($mostCited->citationCount)->toBe(12000);
});

test('can check if prolific', function () {
    expect($this->author->isProlific())->toBeTrue(); // 50 papers is prolific

    $nonProlificAuthor = Author::fromArray([
        'authorId' => '999999',
        'name' => 'New Researcher',
        'paperCount' => 5
    ]);
    expect($nonProlificAuthor->isProlific())->toBeFalse();
});

test('can get primary affiliation', function () {
    $primaryAffiliation = $this->author->getPrimaryAffiliation();

    expect($primaryAffiliation)->toBe('University of Cambridge');
});
