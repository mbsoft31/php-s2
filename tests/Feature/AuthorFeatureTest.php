<?php

use Mbsoft\SemanticScholar\Facades\SemanticScholar;
use Mbsoft\SemanticScholar\DTOs\Author;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

test('can search authors', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/author/search*' => Http::response([
            'total' => 2,
            'offset' => 0,
            'data' => [
                mockAuthorData('1741101', 'Alan Turing'),
                mockAuthorData('1681503', 'John von Neumann'),
            ]
        ], 200)
    ]);

    $authors = SemanticScholar::authors()
        ->search('Alan Turing')
        ->limit(10)
        ->get();

    expect($authors)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($authors)->toHaveCount(2)
        ->and($authors->first())->toBeInstanceOf(Author::class)
        ->and($authors->first()->getName())->toBe('Alan Turing');
});

test('can find author by orcid', function () {
    $orcid = '0000-0002-1825-0097';

    Http::fake([
        "api.semanticscholar.org/graph/v1/author/ORCID:{$orcid}*" => Http::response(
            mockAuthorData('turing-id', 'Alan Turing'),
            200
        )
    ]);

    $author = SemanticScholar::authors()->findByOrcid($orcid);

    expect($author)->toBeInstanceOf(Author::class)
        ->and($author->getName())->toBe('Alan Turing')
        ->and($author->getAuthorId())->toBe('turing-id');
});

test('can find author by id', function () {
    $authorId = '1741101';

    Http::fake([
        "api.semanticscholar.org/graph/v1/author/{$authorId}*" => Http::response(
            mockAuthorData($authorId, 'Alan Turing'),
            200
        )
    ]);

    $author = SemanticScholar::authors()->find($authorId);

    expect($author)->toBeInstanceOf(Author::class)
        ->and($author->getName())->toBe('Alan Turing');
});

test('handles author not found', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response('', 404)
    ]);

    $author = SemanticScholar::authors()->findByOrcid('non-existent-orcid');

    expect($author)->toBeNull();
});

test('can get first author', function () {
    Http::fake([
        'api.semanticscholar.org/graph/v1/author/search*' => Http::response([
            'data' => [
                mockAuthorData('first-author', 'First Author'),
            ]
        ], 200)
    ]);

    $author = SemanticScholar::authors()
        ->search('researcher')
        ->first();

    expect($author)->toBeInstanceOf(Author::class)
        ->and($author->getName())->toBe('First Author');

    assertApiCallMade('author/search', ['limit' => 1]);
});
