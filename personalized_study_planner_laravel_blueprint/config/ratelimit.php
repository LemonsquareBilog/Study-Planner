<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Tiers
    |--------------------------------------------------------------------------
    |
    | Define rate limiting tiers for different types of API endpoints.
    | Each tier specifies the maximum requests per time window.
    |
    */

    'tiers' => [
        // Strict tier - for sensitive operations (auth, password changes)
        'strict' => [
            'limit' => 5,
            'window' => 60, // 5 requests per minute
            'message' => 'Too many attempts. Please try again in a minute.',
        ],

        // Standard tier - for general API usage
        'standard' => [
            'limit' => 60,
            'window' => 60, // 60 requests per minute
            'message' => 'Rate limit exceeded. Please slow down.',
        ],

        // Generous tier - for read-heavy endpoints (dashboard, lists)
        'generous' => [
            'limit' => 120,
            'window' => 60, // 120 requests per minute
            'message' => 'Rate limit exceeded. Please slow down.',
        ],

        // Search tier - for search endpoints
        'search' => [
            'limit' => 30,
            'window' => 60, // 30 requests per minute
            'message' => 'Too many search requests. Please wait a moment.',
        ],

        // Upload tier - for file uploads
        'upload' => [
            'limit' => 10,
            'window' => 300, // 10 uploads per 5 minutes
            'message' => 'Upload limit exceeded. Please wait before uploading more files.',
        ],

        // Admin tier - higher limits for admin operations
        'admin' => [
            'limit' => 200,
            'window' => 60, // 200 requests per minute
            'message' => 'Admin rate limit exceeded.',
        ],

        // Webhook tier - for incoming webhooks
        'webhook' => [
            'limit' => 1000,
            'window' => 60, // 1000 requests per minute
            'message' => 'Webhook rate limit exceeded.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Tier Mapping
    |--------------------------------------------------------------------------
    |
    | Map specific routes to rate limiting tiers.
    | Uses route name patterns for matching.
    |
    */

    'route_tiers' => [
        // Auth endpoints - strict
        'auth.login' => 'strict',
        'auth.register' => 'strict',
        'auth.forgot-password' => 'strict',
        'auth.reset-password' => 'strict',
        'auth.update-password' => 'strict',
        'auth.2fa.*' => 'strict',

        // Search endpoints
        '*.search' => 'search',
        'student.subjects.index' => 'search',
        'student.topics.index' => 'search',
        'student.tasks.index' => 'search',
        'student.quizzes.index' => 'search',
        'admin.resources.index' => 'search',
        'admin.users.index' => 'search',

        // Upload endpoints
        'resources.import' => 'upload',
        'resources.store' => 'upload',

        // Bulk operations - standard
        '*.bulk' => 'standard',
        '*.export' => 'standard',

        // Read-heavy endpoints - generous
        'student.dashboard' => 'generous',
        'student.study-plan.index' => 'generous',
        'student.study-plan.calendar' => 'generous',
        'student.progress' => 'generous',
        'student.progress.export' => 'generous',
        'student.notifications.index' => 'generous',
        'student.recommendations' => 'generous',

        // Admin endpoints - admin tier
        'admin.*' => 'admin',

        // Default - standard
        'default' => 'standard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Rate Limiting
    |--------------------------------------------------------------------------
    */

    'global' => [
        'enabled' => true,
        'limit' => 1000,
        'window' => 60, // 1000 requests per minute globally per IP
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    */

    'headers' => [
        'enabled' => true,
        'limit_header' => 'X-RateLimit-Limit',
        'remaining_header' => 'X-RateLimit-Remaining',
        'reset_header' => 'X-RateLimit-Reset',
        'retry_after_header' => 'Retry-After',
    ],
];