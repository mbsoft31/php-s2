<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Semantic Scholar API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Semantic Scholar API integration
    |
    */

    'base_url' => env('SEMANTIC_SCHOLAR_BASE_URL', 'https://api.semanticscholar.org/graph/v1'),

    /*
    | API Key for increased rate limits and access to additional features
    | Get your key from: https://www.semanticscholar.org/product/api
    */
    'api_key' => env('SEMANTIC_SCHOLAR_API_KEY'),

    /*
    | Default timeout for API requests (in seconds)
    */
    'timeout' => env('SEMANTIC_SCHOLAR_TIMEOUT', 30),

    /*
    | Default cache TTL for API responses (in seconds)
    | Set to null to disable caching by default
    */
    'cache_ttl' => env('SEMANTIC_SCHOLAR_CACHE_TTL', 300),

    /*
    | Rate limiting configuration
    */
    'rate_limit' => [
        'requests_per_second' => env('SEMANTIC_SCHOLAR_RATE_LIMIT', 1),
        'burst_limit' => env('SEMANTIC_SCHOLAR_BURST_LIMIT', 10),
    ],

    /*
    | Default fields to return for different entities
    | These can be overridden in individual queries
    */
    'default_fields' => [
        'papers' => [
            'paperId',
            'title',
            'year',
            'abstract',
            'citationCount',
            'influentialCitationCount',
            'referenceCount',
            'authors',
            'venue',
            'openAccessPdf',
            'externalIds',
            'url',
            'publicationTypes',
            'publicationDate',
            'fieldsOfStudy',
        ],
        'authors' => [
            'authorId',
            'name',
            'paperCount',
            'citationCount',
            'hIndex',
            'externalIds',
            'affiliations',
            'homepage',
            'url',
        ],
        'venues' => [
            'venueId',
            'name',
            'type',
            'url',
            'externalIds',
        ],
    ],

    /*
    | Recommendations API configuration
    */
    'recommendations' => [
        'base_url' => env('SEMANTIC_SCHOLAR_RECOMMENDATIONS_URL', 'https://api.semanticscholar.org/recommendations/v1'),
        'default_limit' => env('SEMANTIC_SCHOLAR_RECOMMENDATIONS_LIMIT', 100),
        'max_positive_papers' => 500,
        'max_negative_papers' => 500,
    ],

    /*
    | Datasets API configuration
    */
    'datasets' => [
        'base_url' => env('SEMANTIC_SCHOLAR_DATASETS_URL', 'https://api.semanticscholar.org/datasets/v1'),
        'default_release' => env('SEMANTIC_SCHOLAR_DEFAULT_RELEASE', 'latest'),
    ],

    /*
    | Bulk operations configuration
    */
    'bulk' => [
        'max_batch_size' => env('SEMANTIC_SCHOLAR_MAX_BATCH_SIZE', 500),
        'default_batch_size' => env('SEMANTIC_SCHOLAR_DEFAULT_BATCH_SIZE', 100),
    ],

    /*
    | Search configuration
    */
    'search' => [
        'default_limit' => env('SEMANTIC_SCHOLAR_SEARCH_LIMIT', 100),
        'max_limit' => env('SEMANTIC_SCHOLAR_SEARCH_MAX_LIMIT', 1000),
        'default_offset' => env('SEMANTIC_SCHOLAR_SEARCH_OFFSET', 0),
    ],

    /*
    | Caching configuration
    */
    'cache' => [
        'store' => env('SEMANTIC_SCHOLAR_CACHE_STORE', 'default'),
        'prefix' => env('SEMANTIC_SCHOLAR_CACHE_PREFIX', 'semantic_scholar'),
        'ttl' => [
            'paper' => env('SEMANTIC_SCHOLAR_PAPER_CACHE_TTL', 3600), // 1 hour
            'author' => env('SEMANTIC_SCHOLAR_AUTHOR_CACHE_TTL', 7200), // 2 hours
            'venue' => env('SEMANTIC_SCHOLAR_VENUE_CACHE_TTL', 14400), // 4 hours
            'search' => env('SEMANTIC_SCHOLAR_SEARCH_CACHE_TTL', 900), // 15 minutes
            'recommendations' => env('SEMANTIC_SCHOLAR_RECOMMENDATIONS_CACHE_TTL', 1800), // 30 minutes
        ],
    ],

    /*
    | Retry configuration for failed requests
    */
    'retry' => [
        'attempts' => env('SEMANTIC_SCHOLAR_RETRY_ATTEMPTS', 3),
        'delay' => env('SEMANTIC_SCHOLAR_RETRY_DELAY', 100), // milliseconds
        'backoff' => env('SEMANTIC_SCHOLAR_RETRY_BACKOFF', true),
    ],

    /*
    | Logging configuration
    */
    'logging' => [
        'enabled' => env('SEMANTIC_SCHOLAR_LOGGING_ENABLED', false),
        'level' => env('SEMANTIC_SCHOLAR_LOG_LEVEL', 'info'),
        'channel' => env('SEMANTIC_SCHOLAR_LOG_CHANNEL', 'default'),
    ],

    /*
    | User Agent string for API requests
    */
    'user_agent' => env('SEMANTIC_SCHOLAR_USER_AGENT', 'Laravel-SemanticScholar/1.0'),

    /*
    | Field mappings for different data types
    | Used to normalize field names across different endpoints
    */
    'field_mappings' => [
        'paper' => [
            'id' => 'paperId',
            'doi' => 'externalIds.DOI',
            'arxiv' => 'externalIds.ArXiv',
            'pubmed' => 'externalIds.PubMed',
            'pdf_url' => 'openAccessPdf.url',
            'is_open_access' => 'isOpenAccess',
        ],
        'author' => [
            'id' => 'authorId',
            'orcid' => 'externalIds.ORCID',
            'dblp' => 'externalIds.DBLP',
            'google_scholar' => 'externalIds.GoogleScholar',
        ],
    ],

    /*
    | Validation rules for API parameters
    */
    'validation' => [
        'paper_id_pattern' => '/^[0-9a-f]{40}$/',
        'author_id_pattern' => '/^\d+$/',
        'doi_pattern' => '/^10\.\d{4,}\/[^\s]+$/',
        'year_range' => [1900, (int) date('Y') + 5],
        'max_query_length' => 500,
    ],

    /*
    | Feature flags
    */
    'features' => [
        'enable_embeddings' => env('SEMANTIC_SCHOLAR_ENABLE_EMBEDDINGS', false),
        'enable_recommendations' => env('SEMANTIC_SCHOLAR_ENABLE_RECOMMENDATIONS', true),
        'enable_datasets' => env('SEMANTIC_SCHOLAR_ENABLE_DATASETS', true),
        'enable_bulk_operations' => env('SEMANTIC_SCHOLAR_ENABLE_BULK', true),
        'enable_caching' => env('SEMANTIC_SCHOLAR_ENABLE_CACHING', true),
        'strict_mode' => env('SEMANTIC_SCHOLAR_STRICT_MODE', false),
    ],

    /*
    | Development and debugging options
    */
    'debug' => [
        'log_requests' => env('SEMANTIC_SCHOLAR_DEBUG_REQUESTS', false),
        'log_responses' => env('SEMANTIC_SCHOLAR_DEBUG_RESPONSES', false),
        'simulate_rate_limits' => env('SEMANTIC_SCHOLAR_SIMULATE_RATE_LIMITS', false),
        'dump_queries' => env('SEMANTIC_SCHOLAR_DUMP_QUERIES', false),
    ],
];
