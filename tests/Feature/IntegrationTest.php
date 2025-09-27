<?php
// tests/Feature/IntegrationTest.php

use Mbsoft\SemanticScholar\Facades\SemanticScholar;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

test('semantic scholar facade works correctly', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
            'data' => [mockPaperData('test', 'Test Paper')]
        ], 200)
    ]);

    $papers = SemanticScholar::searchPapers('test', 5);

    expect($papers)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($papers->first())->toBeInstanceOf(Paper::class)
        ->and($papers->first()->getTitle())->toBe('Test Paper');
});

test('service provider wires dependencies correctly', function () {
    $semanticScholar = app('semantic-scholar');
    $client = $semanticScholar->client();

    expect($semanticScholar)->toBeInstanceOf(\Mbsoft\SemanticScholar\SemanticScholar::class)
        ->and($client)->toBeInstanceOf(\Mbsoft\SemanticScholar\Http\Client::class);
});

test('builder uses client for api calls', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['data' => []], 200)
    ]);

    SemanticScholar::papers()->search('test')->get();

    Http::assertSent(function ($request) {
        return $request->hasHeader('x-api-key', 'test-api-key');
    });
});

test('health check endpoint works', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(mockPaperData(), 200)
    ]);

    $isHealthy = SemanticScholar::test();

    expect($isHealthy)->toBeTrue();
});
