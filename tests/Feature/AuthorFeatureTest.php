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

    try {
        $authors = SemanticScholar::authors()
            ->search('Alan Turing')
            ->limit(10)
            ->get();
    } catch (\Mbsoft\SemanticScholar\Exceptions\SemanticScholarException $e) {
        // show the error for debugging
        dump($e->getMessage());
        $authors = null;
    }

    expect($authors)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($authors)->toHaveCount(2)
        ->and($authors->first())->toBeInstanceOf(Author::class)
        ->and($authors->first()->getName())->toBe('Alan Turing');
});

test('can find author by orcid', function () {
    $orcid = '0000-0002-1825-0097';

    Http::fake([
        "api.semanticscholar.org/graph/v1/author/ORCID:{$orcid}*" => Http::response(
            mockAuthorData($orcid, 'Alan Turing'),
            200
        )
    ]);

    try {
        $author = SemanticScholar::authors()->findByOrcid($orcid);
    } catch (\Mbsoft\SemanticScholar\Exceptions\SemanticScholarException $e) {
        // show the error for debugging
        dump($e->getMessage());
        $author = null;
    }

    expect($author)->toBeInstanceOf(Author::class)
        ->and($author->getName())->toBe('Alan Turing')
        ->and($author->getAuthorId())->toBe('turing-id');
});

test('can find author by id', function () {
    $authorId = '1741101';
    $name = 'Alan Turing';
    Http::fake([
        "api.semanticscholar.org/graph/v1/author/{$authorId}*" => Http::response(
            mockAuthorData($authorId, $name)
        )
    ]);

    try {
        $author = SemanticScholar::authors()->find($authorId);
    } catch (\Mbsoft\SemanticScholar\Exceptions\SemanticScholarException $e) {
        // show the error for debugging
        dump($e->getMessage());
        $author = null;
    }

    expect($author)->toBeInstanceOf(Author::class)
        ->and($author->getName())->toBe($name);
});

test('handles author not found', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response('', 404)
    ]);

    try {
        $author = SemanticScholar::authors()->findByOrcid('non-existent-orcid');
    } catch (\Mbsoft\SemanticScholar\Exceptions\SemanticScholarException $e) {
        $author = null;
    }

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

    try {
        $author = SemanticScholar::authors()
            ->search('Author')
            ->first();
    } catch (\Mbsoft\SemanticScholar\Exceptions\SemanticScholarException $e) {
        // show the error for debugging
        dump($e->getMessage());
        $author = null;
    }

    expect($author)->toBeInstanceOf(Author::class)
        ->and($author->getName())->toBe('First Author');

    assertApiCallMade('author/search', ['limit' => 1]);
});
