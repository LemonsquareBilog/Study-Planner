<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Documentation (OpenAPI/Swagger)
    |--------------------------------------------------------------------------
    |
    | Configuration for generating OpenAPI 3.0 specification.
    |
    */

    'enabled' => env('API_DOCS_ENABLED', true),

    'title' => env('API_DOCS_TITLE', 'Personalized Study Planner API'),
    'description' => env('API_DOCS_DESCRIPTION', 'API documentation for the Personalized Study Planner application'),
    'version' => env('API_DOCS_VERSION', '1.0.0'),

    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost') . '/api/v1',
            'description' => 'Development Server',
        ],
        [
            'url' => 'https://api.studyplanner.example.com/v1',
            'description' => 'Production Server',
        ],
    ],

    'security_schemes' => [
        'bearer' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Laravel Sanctum API token',
        ],
    ],

    'default_security' => ['bearer' => []],

    'tags' => [
        [
            'name' => 'Authentication',
            'description' => 'User registration, login, and authentication management',
        ],
        [
            'name' => 'User Profile',
            'description' => 'Authenticated user profile and settings',
        ],
        [
            'name' => 'Dashboard',
            'description' => 'Student dashboard with progress overview',
        ],
        [
            'name' => 'Study Preferences',
            'description' => 'Study scheduling preferences and onboarding',
        ],
        [
            'name' => 'Subjects',
            'description' => 'Subject and topic management',
        ],
        [
            'name' => 'Tasks',
            'description' => 'Task and assignment management',
        ],
        [
            'name' => 'Study Plans',
            'description' => 'AI-generated study session scheduling',
        ],
        [
            'name' => 'Quizzes',
            'description' => 'Quiz taking and progress tracking',
        ],
        [
            'name' => 'Recommendations',
            'description' => 'Personalized learning resource recommendations',
        ],
        [
            'name' => 'Progress',
            'description' => 'Detailed analytics and performance reports',
        ],
        [
            'name' => 'Notifications',
            'description' => 'Real-time and persistent notifications',
        ],
        [
            'name' => 'Admin - Resources',
            'description' => 'Admin resource library management',
        ],
        [
            'name' => 'Admin - Users',
            'description' => 'Admin user management',
        ],
        [
            'name' => 'Admin - Settings',
            'description' => 'System configuration settings',
        ],
        [
            'name' => 'Admin - Dashboard',
            'description' => 'Admin system analytics and monitoring',
        ],
        [
            'name' => 'Health',
            'description' => 'System health checks',
        ],
    ],

    'paths' => [
        // These will be auto-generated from route annotations
    ],

    'components' => [
        'schemas' => [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'role' => ['type' => 'string', 'enum' => ['student', 'admin']],
                    'course' => ['type' => 'string', 'nullable' => true],
                    'year_level' => ['type' => 'integer', 'nullable' => true],
                    'is_active' => ['type' => 'boolean'],
                    'two_factor_enabled' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'StudyPreference' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'max_daily_minutes' => ['type' => 'integer'],
                    'session_duration_minutes' => ['type' => 'integer'],
                    'break_duration_minutes' => ['type' => 'integer'],
                    'preferred_start_time' => ['type' => 'string', 'format' => 'time'],
                    'preferred_end_time' => ['type' => 'string', 'format' => 'time'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Subject' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'difficulty' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                    'color' => ['type' => 'string'],
                    'color_hex' => ['type' => 'string'],
                    'is_active' => ['type' => 'boolean'],
                    'topics_count' => ['type' => 'integer'],
                    'tasks_count' => ['type' => 'integer'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Task' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'task_type' => ['type' => 'string', 'enum' => ['assignment', 'project', 'exam', 'quiz', 'reading', 'review', 'other']],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'in_progress', 'completed', 'cancelled']],
                    'deadline' => ['type' => 'string', 'format' => 'date-time'],
                    'estimated_minutes' => ['type' => 'integer'],
                    'completed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'subject' => ['$ref' => '#/components/schemas/Subject'],
                    'topic' => ['$ref' => '#/components/schemas/Topic'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'StudySession' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'scheduled_date' => ['type' => 'string', 'format' => 'date'],
                    'start_time' => ['type' => 'string', 'format' => 'time'],
                    'end_time' => ['type' => 'string', 'format' => 'time'],
                    'duration_minutes' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['scheduled', 'in_progress', 'completed', 'skipped']],
                    'started_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'completed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'subject' => ['$ref' => '#/components/schemas/Subject'],
                    'topic' => ['$ref' => '#/components/schemas/Topic'],
                    'task' => ['$ref' => '#/components/schemas/Task'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Quiz' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'total_questions' => ['type' => 'integer'],
                    'topic' => ['$ref' => '#/components/schemas/Topic'],
                    'attempt_count' => ['type' => 'integer'],
                    'last_attempt' => [
                        'type' => 'object',
                        'nullable' => true,
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'percentage' => ['type' => 'number'],
                            'completed_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
            'QuizAttempt' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'in_progress', 'completed']],
                    'score' => ['type' => 'integer'],
                    'total_questions' => ['type' => 'integer'],
                    'percentage' => ['type' => 'number'],
                    'started_at' => ['type' => 'string', 'format' => 'date-time'],
                    'completed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'quiz' => ['$ref' => '#/components/schemas/Quiz'],
                    'answers' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/QuizAnswer'],
                    ],
                ],
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'details' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                    'from' => ['type' => 'integer', 'nullable' => true],
                    'to' => ['type' => 'integer', 'nullable' => true],
                ],
            ],
        ],
        'responses' => [
            'Unauthorized' => [
                'description' => 'Unauthenticated',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'error' => [
                                'code' => 'UNAUTHENTICATED',
                                'message' => 'Unauthenticated. Please log in to continue.',
                                'details' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'Forbidden' => [
                'description' => 'Forbidden',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'NotFound' => [
                'description' => 'Not Found',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'ValidationError' => [
                'description' => 'Validation Error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'error' => [
                                'code' => 'VALIDATION_ERROR',
                                'message' => 'The given data was invalid.',
                                'details' => [
                                    'email' => ['The email field is required.'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'RateLimited' => [
                'description' => 'Rate Limited',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'error' => [
                                'code' => 'RATE_LIMIT_EXCEEDED',
                                'message' => 'Rate limit exceeded. Please slow down.',
                                'details' => [
                                    'retry_after_seconds' => 60,
                                    'limit' => 60,
                                    'window_seconds' => 60,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];