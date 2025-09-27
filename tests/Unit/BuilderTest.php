<?php

use Mbsoft\SemanticScholar\Builder;
use Mbsoft\SemanticScholar\Http\Client;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

beforeEach(function () {
    $this->mockClient = \Mockery::mock(Client::class);
    $this->builder = new Builder($this->mockClient);
});

afterEach(function () {
    \Mockery::close();
});

test('can build search query', function () {
    $this->mockClient
        ->shouldReceive('timeout')->once()->andReturnSelf()
        ->shouldReceive('retry')->once()->andReturnSelf()
        ->shouldReceive('get')
        ->once()
        ->andReturn(['data' => []]);

    $this->builder
        ->endpoint('paper')
        ->search('machine learning')
        ->limit(10)
        ->fields('paperId,title,authors,year,citationCount')
        ->get();

    expect(true)->toBeTrue();
});

test('can build filters', function () {
    $this->mockClient
        ->shouldReceive('timeout')->once()->andReturnSelf()
        ->shouldReceive('retry')->once()->andReturnSelf()
        ->shouldReceive('get')
        ->once()
        ->andReturn(['data' => []]);

    $this->builder
        ->endpoint('paper')
        ->search('AI')
        ->byYear(2024)
        ->minCitations(100)
        ->openAccess()
        ->byFieldOfStudy('Computer Science')
        ->get();

    expect(true)->toBeTrue();
});

test('can set pagination', function () {
    $this->mockClient
        ->shouldReceive('timeout')->once()->andReturnSelf()
        ->shouldReceive('retry')->once()->andReturnSelf()
        ->shouldReceive('get')
        ->once()
        ->andReturn(['data' => []]);

    $this->builder
        ->endpoint('paper')
        ->search('test')
        ->limit(50)
        ->offset(100)
        ->get();

    expect(true)->toBeTrue();
});

test('can set caching', function () {
    $builder = $this->builder->cache(3600);

    $reflection = new \ReflectionClass($builder);
    $cacheProperty = $reflection->getProperty('cacheFor');
    $cacheProperty->setAccessible(true);

    expect($cacheProperty->getValue($builder))->toBe(3600);
});

test('can set retry attempts', function () {
    $builder = $this->builder->retry(5);

    $reflection = new \ReflectionClass($builder);
    $retryProperty = $reflection->getProperty('retryAttempts');
    $retryProperty->setAccessible(true);

    expect($retryProperty->getValue($builder))->toBe(5);
});
