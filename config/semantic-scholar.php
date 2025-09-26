<?php

return [
    'api_key' => env('SEMANTIC_SCHOLAR_API_KEY'),
    'base_url' => env('SEMANTIC_SCHOLAR_BASE_URL', 'https://api.semanticscholar.org/graph/v1'),

    'timeout' => env('SEMANTIC_SCHOLAR_TIMEOUT', 30),
    'retry_attempts' => env('SEMANTIC_SCHOLAR_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('SEMANTIC_SCHOLAR_RETRY_DELAY', 1000),

    'rate_limiting' => [
        'enabled' => env('SEMANTIC_SCHOLAR_RATE_LIMITING', true),
        'requests_per_second' => env('SEMANTIC_SCHOLAR_RPS', 1),
        'with_api_key_rps' => env('SEMANTIC_SCHOLAR_API_KEY_RPS', 100),
    ],

    'cache' => [
        'enabled' => env('SEMANTIC_SCHOLAR_CACHE_ENABLED', true),
        'driver' => env('SEMANTIC_SCHOLAR_CACHE_DRIVER', 'redis'),
        'prefix' => env('SEMANTIC_SCHOLAR_CACHE_PREFIX', 'semantic_scholar'),
        'tags' => env('SEMANTIC_SCHOLAR_CACHE_TAGS', true),
        'default_ttl' => env('SEMANTIC_SCHOLAR_CACHE_TTL', 3600),
        'ttl' => [
            'paper' => env('SEMANTIC_SCHOLAR_PAPER_TTL', 3600),
            'author' => env('SEMANTIC_SCHOLAR_AUTHOR_TTL', 7200),
            'search' => env('SEMANTIC_SCHOLAR_SEARCH_TTL', 900),
            'venue' => env('SEMANTIC_SCHOLAR_VENUE_TTL', 3600),
        ],
    ],

    'logging' => [
        'enabled' => env('SEMANTIC_SCHOLAR_LOGGING', true),
        'channel' => env('SEMANTIC_SCHOLAR_LOG_CHANNEL', 'stack'),
        'level' => env('SEMANTIC_SCHOLAR_LOG_LEVEL', 'info'),
        'log_requests' => env('SEMANTIC_SCHOLAR_LOG_REQUESTS', false),
        'log_responses' => env('SEMANTIC_SCHOLAR_LOG_RESPONSES', false),
        'log_slow_queries' => env('SEMANTIC_SCHOLAR_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('SEMANTIC_SCHOLAR_SLOW_QUERY_THRESHOLD', 2000),
    ],

    'monitoring' => [
        'track_api_usage' => env('SEMANTIC_SCHOLAR_TRACK_USAGE', true),
        'health_check_interval' => env('SEMANTIC_SCHOLAR_HEALTH_CHECK_INTERVAL', 300),
        'circuit_breaker' => [
            'enabled' => env('SEMANTIC_SCHOLAR_CIRCUIT_BREAKER', true),
            'failure_threshold' => env('SEMANTIC_SCHOLAR_CB_FAILURE_THRESHOLD', 5),
            'recovery_timeout' => env('SEMANTIC_SCHOLAR_CB_RECOVERY_TIMEOUT', 60),
        ],
    ],

    'defaults' => [
        'fields' => [
            'paper' => 'paperId,title,abstract,authors,year,citationCount,influentialCitationCount,fieldsOfStudy,publicationDate,venue,openAccessPdf,tldr',
            'author' => 'authorId,name,affiliations,paperCount,citationCount,hIndex',
        ],
        'limit' => env('SEMANTIC_SCHOLAR_DEFAULT_LIMIT', 100),
        'offset' => 0,
    ],
];
