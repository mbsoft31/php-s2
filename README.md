# Laravel Semantic Scholar API Wrapper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mbsoft31/laravel-semantic-scholar.svg?style=flat-square)](https://packagist.org/packages/mbsoft31/laravel-semantic-scholar)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mbsoft31/php-s2/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mbsoft31/php-s2/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mbsoft31/php-s2/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mbsoft31/php-s2/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mbsoft31/laravel-semantic-scholar.svg?style=flat-square)](https://packagist.org/packages/mbsoft31/laravel-semantic-scholar)

A fluent, elegant, and modern wrapper for the [Semantic Scholar Academic Graph API](https://www.semanticscholar.org/product/api), built for Laravel.

## Features

- **Fluent API Design**: Intuitive, chainable query methods
- **Rich Data Transfer Objects**: Strongly-typed responses with helper methods
- **Advanced Caching**: Built-in caching with configurable TTL
- **Memory Efficient**: Cursor-based pagination for large datasets
- **Academic Utilities**: BibTeX generation, citation analysis, impact metrics
- **Multiple ID Support**: DOI, ArXiv, PubMed, ORCID, and more
- **Rate Limit Handling**: Automatic retry with exponential backoff
- **Laravel Integration**: Service provider, facade, and configuration

## Installation

You can install the package via Composer:

```bash
composer require mbsoft31/laravel-semantic-scholar
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Mbsoft\SemanticScholar\SemanticScholarServiceProvider" --tag="config"
```

Optionally, set your API key in your `.env` file for increased rate limits:

```env
SEMANTIC_SCHOLAR_API_KEY=your-api-key-here
```

## Quick Start

```php
use Mbsoft\SemanticScholar\Facades\SemanticScholar;

// Search for papers
$papers = SemanticScholar::papers()
    ->search('machine learning')
    ->byYear(2024)
    ->minCitations(10)
    ->openAccess()
    ->limit(50)
    ->get();

// Find a specific paper
$paper = SemanticScholar::papers()->find('649def34f8be52c8b66281af98ae884c09aef38b');
echo $paper->title;
echo $paper->toBibTeX();

// Search authors
$authors = SemanticScholar::authors()
    ->search('Geoffrey Hinton')
    ->get();

// Find author by ORCID
$author = SemanticScholar::authors()->findByOrcid('0000-0002-1825-0097');
echo "H-Index: " . $author->hIndex;
echo "Total Papers: " . $author->paperCount;
```

## Advanced Usage

### Paper Analysis

```php
$paper = SemanticScholar::papers()->findByDoi('10.1038/nature14539');

// Academic metrics
echo $paper->getCitationVelocity(); // Citations per year
echo $paper->getInfluentialCitationRatio(); // Quality metric
echo $paper->isHighlyInfluential(); // Boolean flag
echo $paper->getTldr(); // AI-generated summary

// Citation formats
echo $paper->toBibTeX();
echo $paper->toApa();
echo $paper->toMla();

// Access metadata
echo $paper->getDoi();
echo $paper->getOpenAccessUrl();
echo implode(', ', $paper->getAuthorNames());
```

### Author Analytics

```php
$author = SemanticScholar::authors()->find('1741101');

// Career analysis
$careerSpan = $author->getCareerSpan();
echo "Active from {$careerSpan['start_year']} to {$careerSpan['end_year']}";

// Productivity metrics
echo $author->getProductivityLevel(); // 'highly_productive', 'productive', etc.
echo $author->getProductivityTrend(); // 'increasing', 'stable', 'decreasing'
echo $author->getPeakProductivityYear();

// Impact analysis
echo $author->getImpactLevel(); // 'exceptional', 'high', 'significant', etc.
echo $author->getAverageCitationsPerPaper();
echo $author->getCollaborationNetworkSize();
```

### Memory-Efficient Processing

```php
// Process large datasets without memory issues
SemanticScholar::papers()
    ->search('deep learning')
    ->byYear(2023)
    ->cursor()
    ->chunk(1000)
    ->each(function ($papers) {
        foreach ($papers as $paper) {
            echo $paper->title . "\n";
        }
    });
```

### Caching

```php
// Cache for 10 minutes
$papers = SemanticScholar::papers()
    ->search('neural networks')
    ->cacheFor(600)
    ->get();

// Cache forever
$author = SemanticScholar::authors()
    ->find('1741101')
    ->cacheForever()
    ->get();
```

### Pagination

```php
// Automatic pagination
$paginatedPapers = SemanticScholar::papers()
    ->search('computer vision')
    ->paginate(perPage: 25, page: 2);

echo "Total papers: " . $paginatedPapers->total();
echo "Current page: " . $paginatedPapers->currentPage();
```

### Field Selection

```php
// Only fetch specific fields to improve performance
$papers = SemanticScholar::papers()
    ->search('artificial intelligence')
    ->fields(['paperId', 'title', 'year', 'citationCount', 'authors'])
    ->get();
```

## Configuration

The configuration file allows you to customize various aspects of the package:

```php
return [
    'api_key' => env('SEMANTIC_SCHOLAR_API_KEY'),
    'base_url' => env('SEMANTIC_SCHOLAR_BASE_URL', 'https://api.semanticscholar.org/graph/v1'),
    'timeout' => env('SEMANTIC_SCHOLAR_TIMEOUT', 30),
    'cache_ttl' => env('SEMANTIC_SCHOLAR_CACHE_TTL', 300),
    
    'default_fields' => [
        'papers' => [
            'paperId', 'title', 'year', 'abstract',
            'citationCount', 'authors', 'venue', 'openAccessPdf'
        ],
        'authors' => [
            'authorId', 'name', 'paperCount', 'citationCount',
            'hIndex', 'affiliations'
        ]
    ],
    
    'rate_limit' => [
        'requests_per_second' => 1,
        'burst_limit' => 10,
    ],
];
```

## Error Handling

```php
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

try {
    $papers = SemanticScholar::papers()
        ->search('quantum computing')
        ->get();
} catch (SemanticScholarException $e) {
    logger()->error('Semantic Scholar API error: ' . $e->getMessage());
    
    // Handle specific error types
    if ($e->getCode() === 429) {
        // Rate limit exceeded
    }
}
```

## API Methods

### Papers

- `papers()` - Start a papers query
- `search(string $query)` - Search papers by text
- `find(string $id)` - Find paper by Semantic Scholar ID
- `findByDoi(string $doi)` - Find paper by DOI
- `findByArxiv(string $arxivId)` - Find paper by ArXiv ID
- `findByPubmed(string $pubmedId)` - Find paper by PubMed ID
- `byYear(int $year)` - Filter by publication year
- `byYearRange(int $start, int $end)` - Filter by year range
- `minCitations(int $count)` - Minimum citation count
- `openAccess(bool $required = true)` - Open access papers only
- `byFieldOfStudy(string $field)` - Filter by field of study
- `byVenue(string $venue)` - Filter by publication venue

### Authors

- `authors()` - Start an authors query
- `search(string $query)` - Search authors by name
- `find(string $id)` - Find author by Semantic Scholar ID
- `findByOrcid(string $orcid)` - Find author by ORCID

### Common Methods

- `fields(array $fields)` - Select specific fields
- `limit(int $limit)` - Limit number of results
- `offset(int $offset)` - Skip number of results
- `get()` - Execute query and get Collection
- `paginate(int $perPage, int $page)` - Get paginated results
- `cursor()` - Get LazyCollection for memory efficiency
- `cacheFor(int $seconds)` - Cache results
- `cacheForever()` - Cache results indefinitely

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mouadh BEKHOUCHE](https://github.com/mbsoft31)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.