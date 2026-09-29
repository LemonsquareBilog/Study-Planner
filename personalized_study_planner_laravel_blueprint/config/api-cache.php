<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Response Caching
    |--------------------------------------------------------------------------
    |
    | Configure which endpoints should be cached and for how long.
    | Caching is applied via the 'api.cache' middleware.
    |
    */

    'enabled' => env('API_CACHE_ENABLED', true),

    'default_store' => env('API_CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Configuration (in seconds)
    |--------------------------------------------------------------------------
    */

    'ttl' => [
        // Very short - for frequently changing data
        'short' => 30,      // 30 seconds
        'minute' => 60,     // 1 minute
        
        // Medium - for moderately changing data
        'medium' => 300,    // 5 minutes
        'quarter' => 900,   // 15 minutes
        
        // Long - for relatively static data
        'long' => 1800,     // 30 minutes
        'hour' => 3600,     // 1 hour
        'day' => 86400,     // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Cache Rules
    |--------------------------------------------------------------------------
    |
    | Define which routes get cached and with what TTL.
    | Key format: route.name -> cache configuration
    |
    */

    'rules' => [
        // Student dashboard - cache for 1 minute (personalized but not real-time critical)
        'v1.student.dashboard' => [
            'ttl' => 'minute',
            'tags' => ['user:{user_id}', 'dashboard'],
            'vary' => ['user_id'],
        ],

        // Study plan calendar - cache for 5 minutes
        'v1.student.study-plan.calendar' => [
            'ttl' => 'medium',
            'tags' => ['user:{user_id}', 'study-plan'],
            'vary' => ['user_id', 'from', 'to'],
        ],

        // Recommendations - cache for 15 minutes (updated after quiz completion)
        'v1.student.recommendations.index' => [
            'ttl' => 'quarter',
            'tags' => ['user:{user_id}', 'recommendations'],
            'vary' => ['user_id'],
        ],

        // Progress analytics - cache for 5 minutes
        'v1.student.progress.index' => [
            'ttl' => 'medium',
            'tags' => ['user:{user_id}', 'progress'],
            'vary' => ['user_id'],
        ],

        // Subject list - cache for 30 minutes (changes infrequently)
        'v1.student.subjects.index' => [
            'ttl' => 'long',
            'tags' => ['user:{user_id}', 'subjects'],
            'vary' => ['user_id', 'search'],
        ],

        // Topic list - cache for 30 minutes
        'v1.student.topics.index' => [
            'ttl' => 'long',
            'tags' => ['user:{user_id}', 'topics', 'subject:{subject_id}'],
            'vary' => ['user_id', 'subject_id', 'search'],
        ],

        // Quiz list - cache for 15 minutes
        'v1.student.quizzes.index' => [
            'ttl' => 'quarter',
            'tags' => ['user:{user_id}', 'quizzes'],
            'vary' => ['user_id', 'search'],
        ],

        // Admin dashboard - cache for 5 minutes
        'v1.admin.dashboard' => [
            'ttl' => 'medium',
            'tags' => ['admin:dashboard'],
            'vary' => [],
        ],

        // Admin analytics - cache for 15 minutes
        'v1.admin.analytics' => [
            'ttl' => 'quarter',
            'tags' => ['admin:analytics'],
            'vary' => [],
        ],

        // System settings - cache for 1 hour (rarely changes)
        'v1.admin.system-settings.index' => [
            'ttl' => 'hour',
            'tags' => ['system:settings'],
            'vary' => [],
        ],

        // Resources list - cache for 15 minutes
        'v1.admin.resources.index' => [
            'ttl' => 'quarter',
            'tags' => ['admin:resources'],
            'vary' => ['search', 'resource_type', 'difficulty'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => 'api:v1',

    /*
    |--------------------------------------------------------------------------
    | Conditional Caching
    |--------------------------------------------------------------------------
    */

    'conditional' => [
        // Don't cache if these query parameters are present
        'skip_params' => ['no_cache', 'fresh', 'debug'],
        
        // Don't cache for these user roles
        'skip_roles' => [],
        
        // Only cache successful responses
        'only_success' => true,
    ],
];