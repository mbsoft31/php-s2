<?php
// tests/Pest.php - Pest configuration

use Mbsoft\SemanticScholar\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function mockPaperData(string $id = 'test-id', string $title = 'Test Paper', array $additional = []): array
{
    return array_merge([
        'paperId' => $id,
        'title' => $title,
        'abstract' => 'Test abstract for ' . $title,
        'year' => 2024,
        'citationCount' => 100,
        'influentialCitationCount' => 15,
        'fieldsOfStudy' => ['Computer Science'],
        'authors' => [
            ['authorId' => 'author1', 'name' => 'Test Author']
        ],
        'venue' => 'Test Conference',
        'publicationDate' => '2024-01-01',
        'openAccessPdf' => [
            'url' => 'https://example.com/paper.pdf',
            'status' => 'GREEN'
        ]
    ], $additional);
}

function mockAuthorData(string $id = 'test-author-id', string $name = 'Test Author', array $additional = []): array
{
    return array_merge([
        'authorId' => $id,
        'name' => $name,
        'affiliations' => ['Test University'],
        'paperCount' => 10,
        'citationCount' => 500,
        'hIndex' => 8,
        'papers' => [
            ['paperId' => 'paper1', 'title' => 'Paper 1', 'year' => 2023, 'citationCount' => 50],
            ['paperId' => 'paper2', 'title' => 'Paper 2', 'year' => 2024, 'citationCount' => 75],
        ]
    ], $additional);
}

function assertApiCallMade(string $endpoint, array $expectedParams = []): void
{
    \Illuminate\Support\Facades\Http::assertSent(function ($request) use ($endpoint, $expectedParams) {
        $urlMatches = str_contains($request->url(), $endpoint);

        if (empty($expectedParams)) {
            return $urlMatches;
        }

        foreach ($expectedParams as $key => $value) {
            if (!isset($request[$key]) || $request[$key] !== $value) {
                return false;
            }
        }

        return $urlMatches;
    });
}
