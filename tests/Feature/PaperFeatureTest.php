<?php

use Mbsoft\SemanticScholar\Facades\SemanticScholar;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

test('can search papers', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'total' => 2,
            'offset' => 0,
            'next' => null,
            'data' => [
                mockPaperData('123', 'Machine Learning Basics'),
                mockPaperData('456', 'Deep Learning Advanced'),
            ]
        ], 200)
    ]);

    $papers = SemanticScholar::papers()
        ->search('machine learning')
        ->limit(10)
        ->get();

    expect($papers)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($papers)->toHaveCount(2)
        ->and($papers->first())->toBeInstanceOf(Paper::class)
        ->and($papers->first()->getTitle())->toBe('Machine Learning Basics');

    assertApiCallMade('paper/search', [
        'query' => 'machine learning',
        'limit' => 10
    ]);
});

test('can find paper by doi', function () {
    $doi = '10.1093/mind/lix.236.433';

    Http::fake([
        "api.semanticscholar.org/graph/v1/paper/DOI:{$doi}*" => Http::response(
            mockPaperData('computing-machinery', 'Computing Machinery and Intelligence'),
            200
        )
    ]);

    $paper = SemanticScholar::papers()->findByDoi($doi);

    expect($paper)->toBeInstanceOf(Paper::class)
        ->and($paper->getTitle())->toBe('Computing Machinery and Intelligence')
        ->and($paper->getPaperId())->toBe('computing-machinery');
});

test('can find paper by arxiv id', function () {
    $arxivId = '1706.03762';

    Http::fake([
        "api.semanticscholar.org/graph/v1/paper/ARXIV:{$arxivId}*" => Http::response(
            mockPaperData('attention-all-you-need', 'Attention Is All You Need'),
            200
        )
    ]);

    $paper = SemanticScholar::papers()->findByArxiv($arxivId);

    expect($paper)->toBeInstanceOf(Paper::class)
        ->and($paper->getTitle())->toBe('Attention Is All You Need');
});

test('handles paper not found', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response('', 404)
    ]);

    $paper = SemanticScholar::papers()->findByDoi('non-existent-doi');

    expect($paper)->toBeNull();
});

test('can filter papers by year', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'data' => [
                mockPaperData('paper1', 'Paper 2024', ['year' => 2024]),
            ]
        ], 200)
    ]);

    $papers = SemanticScholar::papers()
        ->search('AI')
        ->byYear(2024)
        ->get();

    assertApiCallMade('paper/search', ['year' => 2024]);
    expect($papers)->toHaveCount(1);
});

test('can filter by min citations', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'data' => [
                mockPaperData('highly-cited', 'Highly Cited Paper', ['citationCount' => 1000]),
            ]
        ], 200)
    ]);

    $papers = SemanticScholar::papers()
        ->search('neural networks')
        ->minCitations(500)
        ->get();

    assertApiCallMade('paper/search', ['minCitationCount' => 500]);
    expect($papers)->toHaveCount(1)
        ->and($papers->first()->getCitationCount())->toBe(1000);
});

test('can filter by open access', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'data' => [
                mockPaperData('open-paper', 'Open Access Paper'),
            ]
        ], 200)
    ]);

    $papers = SemanticScholar::papers()
        ->search('machine learning')
        ->openAccess()
        ->get();

    assertApiCallMade('paper/search', ['openAccessPdf' => 'true']);
    expect($papers)->toHaveCount(1);
});

test('handles rate limit errors', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'error' => 'Rate limit exceeded'
        ], 429)
    ]);

    expect(fn() => SemanticScholar::papers()->search('test')->get())
        ->toThrow(SemanticScholarException::class, 'Rate limit exceeded');
});

test('can use pagination', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'total' => 100,
            'offset' => 0,
            'next' => 'next-token',
            'data' => [
                mockPaperData('paper1', 'Paper 1'),
                mockPaperData('paper2', 'Paper 2'),
            ]
        ], 200)
    ]);

    $papers = SemanticScholar::papers()
        ->search('deep learning')
        ->limit(2)
        ->offset(0)
        ->get();

    assertApiCallMade('paper/search', [
        'limit' => 2,
        'offset' => 0
    ]);
    expect($papers)->toHaveCount(2);
});

test('can get first paper', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'data' => [
                mockPaperData('first-paper', 'First Paper'),
            ]
        ], 200)
    ]);

    $paper = SemanticScholar::papers()
        ->search('AI')
        ->first();

    expect($paper)->toBeInstanceOf(Paper::class)
        ->and($paper->getTitle())->toBe('First Paper');

    assertApiCallMade('paper/search', ['limit' => 1]);
});
