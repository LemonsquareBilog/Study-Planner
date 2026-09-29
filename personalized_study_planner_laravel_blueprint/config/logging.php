<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | logs to the storage. The default value is "stack", which combines
    | multiple channels.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Configure log channels for different purposes.
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'api'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
            'replace_placeholders' => true,
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => env('API_LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
            'permission' => 0644,
        ],

        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'slow_queries' => [
            'driver' => 'daily',
            'path' => storage_path('logs/slow-queries.log'),
            'level' => 'warning',
            'days' => 30,
            'replace_placeholders' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Logging Configuration
    |--------------------------------------------------------------------------
    */

    'api_requests' => env('API_LOG_REQUESTS', true),
    'api_responses' => env('API_LOG_RESPONSES', true),
    'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD', 1000), // milliseconds
    'store_in_database' => env('API_LOG_STORE_IN_DATABASE', true),
];