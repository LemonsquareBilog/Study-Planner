<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    */

    'enabled' => env('EMAIL_NOTIFICATIONS_ENABLED', true),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@studyplanner.example.com'),
        'name' => env('MAIL_FROM_NAME', 'Personalized Study Planner'),
    ],

    'templates' => [
        'welcome' => [
            'subject' => 'Welcome to Personalized Study Planner!',
            'view' => 'emails.welcome',
        ],
        'password_reset' => [
            'subject' => 'Reset Your Password',
            'view' => 'emails.password-reset',
        ],
        'study_session_reminder' => [
            'subject' => 'Upcoming Study Session Reminder',
            'view' => 'emails.study-session-reminder',
        ],
        'task_deadline_approaching' => [
            'subject' => 'Task Deadline Approaching',
            'view' => 'emails.task-deadline',
        ],
        'quiz_completed' => [
            'subject' => 'Quiz Completed - Your Results',
            'view' => 'emails.quiz-completed',
        ],
        'study_plan_generated' => [
            'subject' => 'Your New Study Plan is Ready',
            'view' => 'emails.study-plan-generated',
        ],
        'weekly_progress' => [
            'subject' => 'Your Weekly Progress Report',
            'view' => 'emails.weekly-progress',
        ],
        'admin_user_registered' => [
            'subject' => 'New User Registered',
            'view' => 'emails.admin.user-registered',
        ],
        'admin_system_alert' => [
            'subject' => 'System Alert',
            'view' => 'emails.admin.system-alert',
        ],
    ],

    'queue' => env('EMAIL_QUEUE', 'emails'),

    'retry' => [
        'attempts' => 3,
        'backoff' => [60, 300, 900], // 1min, 5min, 15min
    ],

    'unsubscribe' => [
        'enabled' => true,
        'route' => 'notifications.unsubscribe',
    ],
];