<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebSocket/Pusher Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time features using Pusher or Laravel Echo Server.
    |
    */

    'enabled' => env('WEBSOCKETS_ENABLED', true),

    'driver' => env('WEBSOCKETS_DRIVER', 'pusher'), // pusher, laravel-echo-server, ably

    'pusher' => [
        'app_id' => env('PUSHER_APP_ID'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
        'encrypted' => env('PUSHER_APP_ENCRYPTED', true),
        'host' => env('PUSHER_HOST'),
        'port' => env('PUSHER_PORT'),
        'scheme' => env('PUSHER_SCHEME', 'https'),
    ],

    'laravel_echo_server' => [
        'host' => env('LARAVEL_ECHO_SERVER_HOST', 'localhost'),
        'port' => env('LARAVEL_ECHO_SERVER_PORT', 6001),
        'scheme' => env('LARAVEL_ECHO_SERVER_SCHEME', 'http'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [
        // User-specific private channel
        'user' => [
            'pattern' => 'user.{user_id}',
            'events' => [
                'notification.created',
                'notification.read',
                'study_session.reminder',
                'task.deadline_approaching',
                'quiz.attempt.completed',
                'study_plan.generated',
            ],
        ],

        // Admin channel for system-wide events
        'admin' => [
            'pattern' => 'admin.system',
            'events' => [
                'user.registered',
                'user.deactivated',
                'resource.created',
                'system.health_check_failed',
            ],
        ],

        // Study session collaboration channel
        'study_session' => [
            'pattern' => 'study_session.{session_id}',
            'events' => [
                'study_session.started',
                'study_session.completed',
                'study_session.skipped',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presence Channels
    |--------------------------------------------------------------------------
    */

    'presence' => [
        'enabled' => true,
        'channels' => [
            'study_room.{room_id}' => [
                'max_members' => 50,
                'events' => [
                    'user.joined',
                    'user.left',
                    'user.status_changed',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Events (for direct client-to-client communication)
    |--------------------------------------------------------------------------
    */

    'client_events' => [
        'enabled' => true,
        'prefix' => 'client-',
        'max_payload_kb' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    'authorization' => [
        'callback' => \App\Http\Controllers\BroadcastingController::class . '@authorize',
    ],
];