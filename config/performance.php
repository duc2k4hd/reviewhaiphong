<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure cache durations and keys for different parts of the application
    |
    */
    'cache' => [
        'posts' => [
            'popular_duration' => 30, // minutes
            'category_posts_duration' => 30, // minutes
            'search_results_duration' => 15, // minutes
        ],
        'categories' => [
            'duration' => 120, // minutes (2 hours)
        ],
        'sitemap' => [
            'duration' => 60, // minutes (1 hour)
        ],
        'views' => [
            'ip_cooldown' => 6, // hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for database query optimization
    |
    */
    'query' => [
        'pagination' => [
            'posts_per_page' => 20,
            'comments_per_post' => 20,
            'popular_posts_limit' => 15,
            'related_posts_limit' => 8,
        ],
        'eager_loading' => [
            'enabled' => true,
            'default_relations' => [
                'posts' => ['category', 'account.profile'],
                'categories' => ['parent'],
                'comments' => ['account.profile'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for search functionality
    |
    */
    'search' => [
        'min_keyword_length' => 2,
        'max_results' => 50,
        'use_fulltext' => env('SEARCH_USE_FULLTEXT', true),
        'cache_duration' => 15, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for frontend performance
    |
    */
    'frontend' => [
        'enable_compression' => true,
        'enable_browser_caching' => true,
        'cdn_enabled' => env('CDN_ENABLED', false),
        'cdn_url' => env('CDN_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Performance monitoring settings
    |
    */
    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING', true),
        'slow_query_threshold' => 1000, // milliseconds
        'log_slow_queries' => true,
    ],
];