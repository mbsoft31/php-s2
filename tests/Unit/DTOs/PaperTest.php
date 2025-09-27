<?php

use Mbsoft\SemanticScholar\DTOs\Paper;
use Mbsoft\SemanticScholar\DTOs\Author;

beforeEach(function () {
    $this->paperData = [
        'paperId' => '649def34f8be52c8b66281af98ae884c09aef38b',
        'title' => 'Attention Is All You Need',
        'abstract' => 'The dominant sequence transduction models...',
        'year' => 2017,
        'publicationDate' => '2017-06-12',
        'citationCount' => 50000,
        'influentialCitationCount' => 8500,
        'fieldsOfStudy' => ['Computer Science', 'Mathematics'],
        'venue' => 'Neural Information Processing Systems',
        'authors' => [
            [
                'authorId' => 'author1',
                'name' => 'Ashish Vaswani'
            ],
            [
                'authorId' => 'author2',
                'name' => 'Noam Shazeer'
            ]
        ],
        'openAccessPdf' => [
            'url' => 'https://arxiv.org/pdf/1706.03762.pdf',
            'status' => 'GREEN'
        ],
        'tldr' => [
            'model' => 'tldr@v2.0.0',
            'text' => 'The Transformer, a model architecture eschewing recurrence and instead relying entirely on an attention mechanism to draw global dependencies between input and output, is introduced.'
        ]
    ];

    $this->paper = Paper::from($this->paperData);
});

test('can get basic properties', function () {
    expect($this->paper->getPaperId())->toBe('649def34f8be52c8b66281af98ae884c09aef38b')
        ->and($this->paper->getTitle())->toBe('Attention Is All You Need')
        ->and($this->paper->getAbstract())->toBe('The dominant sequence transduction models...')
        ->and($this->paper->getYear())->toBe(2017)
        ->and($this->paper->getCitationCount())->toBe(50000)
        ->and($this->paper->getInfluentialCitationCount())->toBe(8500);
});

test('can get authors collection', function () {
    $authors = $this->paper->getAuthors();

    expect($authors)->toHaveCount(2)
        ->and($authors->first())->toBeInstanceOf(Author::class)
        ->and($authors->first()->getName())->toBe('Ashish Vaswani')
        ->and($authors->last()->getName())->toBe('Noam Shazeer');
});

test('can calculate citation velocity', function () {
    $velocity = $this->paper->getCitationVelocity();
    $expectedVelocity = 50000 / (now()->year - 2017 + 1);

    expect($velocity)->toBe($expectedVelocity);
});

test('can calculate influential citation ratio', function () {
    $ratio = $this->paper->getInfluentialCitationRatio();

    expect($ratio)->toBe(0.17); // 8500/50000 = 0.17
});

test('can determine if highly influential', function () {
    expect($this->paper->isHighlyInfluential())->toBeTrue();

    // Test with low influential citations
    $lowInfluentialPaper = Paper::from([
        'citationCount' => 1000,
        'influentialCitationCount' => 50
    ]);

    expect($lowInfluentialPaper->isHighlyInfluential())->toBeFalse();
});

test('can get tldr', function () {
    $tldr = $this->paper->getTldr();

    expect($tldr)->toContain('Transformer')
        ->and($tldr)->toContain('attention mechanism');
});

test('can get fields of study', function () {
    $fields = $this->paper->getFieldsOfStudy();

    expect($fields)->toHaveCount(2)
        ->and($fields->contains('Computer Science'))->toBeTrue()
        ->and($fields->contains('Mathematics'))->toBeTrue();
});

test('can check open access availability', function () {
    expect($this->paper->isOpenAccess())->toBeTrue();

    $openAccessUrls = $this->paper->getOpenAccessUrls();
    expect($openAccessUrls)->toHaveCount(1)
        ->and($openAccessUrls->first())->toBe('https://arxiv.org/pdf/1706.03762.pdf');
});

test('can calculate impact score', function () {
    $impactScore = $this->paper->getImpactScore();

    expect($impactScore)->toBeFloat()
        ->and($impactScore)->toBeGreaterThan(0);
});

test('can get academic age', function () {
    $age = $this->paper->getAcademicAge();
    $expectedAge = now()->year - 2017;

    expect($age)->toBe($expectedAge);
});

test('can check if recent publication', function () {
    expect($this->paper->isRecentPublication())->toBeFalse(); // 2017 is not recent

    $recentPaper = Paper::from(['year' => now()->year]);
    expect($recentPaper->isRecentPublication())->toBeTrue();
});

test('can export to bibtex', function () {
    $bibtex = $this->paper->toBibTeX();

    expect($bibtex)->toContain('@article')
        ->and($bibtex)->toContain('Attention Is All You Need')
        ->and($bibtex)->toContain('Ashish Vaswani')
        ->and($bibtex)->toContain('2017');
});

test('can export to apa', function () {
    $apa = $this->paper->toApa();

    expect($apa)->toContain('Vaswani, A.')
        ->and($apa)->toContain('Shazeer, N.')
        ->and($apa)->toContain('(2017)')
        ->and($apa)->toContain('Attention Is All You Need');
});

test('can export to mla', function () {
    $mla = $this->paper->toMla();

    expect($mla)->toContain('Vaswani, Ashish')
        ->and($mla)->toContain('Attention Is All You Need')
        ->and($mla)->toContain('2017');
});

test('has title', function () {
    expect($this->paper->hasTitle())->toBeTrue();

    $paperWithoutTitle = Paper::from([]);
    expect($paperWithoutTitle->hasTitle())->toBeFalse();
});

test('has abstract', function () {
    expect($this->paper->hasAbstract())->toBeTrue();

    $paperWithoutAbstract = Paper::from([]);
    expect($paperWithoutAbstract->hasAbstract())->toBeFalse();
});

test('can get author names string', function () {
    $authorNames = $this->paper->getAuthorNamesString();

    expect($authorNames)->toBe('Ashish Vaswani, Noam Shazeer');
});

test('can get first author', function () {
    $firstAuthor = $this->paper->getFirstAuthor();

    expect($firstAuthor)->toBeInstanceOf(Author::class)
        ->and($firstAuthor->getName())->toBe('Ashish Vaswani');
});

test('can get publication venue', function () {
    $venue = $this->paper->getVenue();

    expect($venue)->toBe('Neural Information Processing Systems');
});
