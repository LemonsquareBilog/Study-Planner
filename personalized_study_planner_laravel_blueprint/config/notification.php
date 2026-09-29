<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Scheduling
    |--------------------------------------------------------------------------
    */

    'study_session_reminders' => [1440, 60, 15], // 24 hours, 1 hour, 15 minutes before

    'task_deadline_reminders' => [168, 24, 1], // 1 week, 24 hours, 1 hour before

    'weekly_progress_day' => 1, // Monday (0 = Sunday, 1 = Monday, etc.)

    'weekly_progress_time' => '09:00', // 9 AM

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'welcome' => 'emails.welcome',
        'password_reset' => 'emails.password-reset',
        'study_session_reminder' => 'emails.study-session-reminder',
        'task_deadline_approaching' => 'emails.task-deadline',
        'quiz_completed' => 'emails.quiz-completed',
        'study_plan_generated' => 'emails.study-plan-generated',
        'weekly_progress' => 'emails.weekly-progress',
        'admin_user_registered' => 'emails.admin.user-registered',
        'admin_system_alert' => 'emails.admin.system-alert',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => env('NOTIFICATION_QUEUE', 'notifications'),

    'retry_after' => 90, // seconds

    'max_attempts' => 3,

    'backoff' => [60, 300, 900], // 1min, 5min, 15min
];