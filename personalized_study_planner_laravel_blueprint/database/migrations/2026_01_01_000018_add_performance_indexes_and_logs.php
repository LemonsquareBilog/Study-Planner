<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table - additional indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active'], 'users_role_active_idx');
            $table->index('email', 'users_email_idx');
        });

        // Study preferences - already has unique user_id
        
        // Subjects - additional indexes
        Schema::table('subjects', function (Blueprint $table) {
            $table->index(['user_id', 'is_active'], 'subjects_user_active_idx');
            $table->index(['user_id', 'name'], 'subjects_user_name_idx');
        });

        // Topics - additional indexes
        Schema::table('topics', function (Blueprint $table) {
            $table->index(['subject_id', 'is_active'], 'topics_subject_active_idx');
            $table->index(['subject_id', 'name'], 'topics_subject_name_idx');
        });

        // Tasks - comprehensive indexes for filtering and sorting
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'deadline'], 'tasks_user_status_deadline_idx');
            $table->index(['user_id', 'priority', 'deadline'], 'tasks_user_priority_deadline_idx');
            $table->index(['user_id', 'subject_id', 'status'], 'tasks_user_subject_status_idx');
            $table->index(['user_id', 'topic_id', 'status'], 'tasks_user_topic_status_idx');
            $table->index(['deadline', 'status'], 'tasks_deadline_status_idx');
            $table->index(['user_id', 'completed_at'], 'tasks_user_completed_idx');
        });

        // Study sessions - comprehensive indexes
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'scheduled_date'], 'sessions_user_status_date_idx');
            $table->index(['user_id', 'scheduled_date', 'start_time'], 'sessions_user_date_time_idx');
            $table->index(['user_id', 'subject_id', 'status'], 'sessions_user_subject_status_idx');
            $table->index(['task_id', 'status'], 'sessions_task_status_idx');
            $table->index(['scheduled_date', 'status'], 'sessions_date_status_idx');
        });

        // Quizzes - additional indexes
        Schema::table('quizzes', function (Blueprint $table) {
            $table->index(['topic_id', 'is_active'], 'quizzes_topic_active_idx');
        });

        // Quiz attempts - comprehensive indexes
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'completed_at'], 'attempts_user_status_completed_idx');
            $table->index(['user_id', 'quiz_id', 'status'], 'attempts_user_quiz_status_idx');
            $table->index(['quiz_id', 'status', 'completed_at'], 'attempts_quiz_status_completed_idx');
        });

        // Quiz answers - additional indexes
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->index(['quiz_attempt_id', 'question_id'], 'answers_attempt_question_idx');
        });

        // Resources - additional indexes
        Schema::table('resources', function (Blueprint $table) {
            $table->index(['resource_type', 'is_active'], 'resources_type_active_idx');
            $table->index(['difficulty', 'is_active'], 'resources_difficulty_active_idx');
        });

        // Notifications - comprehensive indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at', 'created_at'], 'notifs_user_read_created_idx');
            $table->index(['user_id', 'type', 'read_at'], 'notifs_user_type_read_idx');
            $table->index(['type', 'delivery_status'], 'notifs_type_delivery_idx');
        });

        // Recommendation views - additional indexes
        Schema::table('recommendation_views', function (Blueprint $table) {
            $table->index(['user_id', 'resource_id', 'viewed_at'], 'rec_views_user_resource_viewed_idx');
            $table->index(['topic_id', 'viewed_at'], 'rec_views_topic_viewed_idx');
        });

        // Study plan generation logs (new table for tracking generation history)
        Schema::create('study_plan_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('created_sessions')->default(0);
            $table->string('reason')->nullable();
            $table->json('diagnostic_data')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at'], 'plan_gen_user_created_idx');
        });

        // API request logs (for monitoring)
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->index();
            $table->integer('status_code')->index();
            $table->integer('response_time_ms')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->timestamps();
            
            $table->index(['created_at', 'status_code'], 'api_logs_created_status_idx');
            $table->index(['user_id', 'created_at'], 'api_logs_user_created_idx');
            $table->index(['path', 'created_at'], 'api_logs_path_created_idx');
        });

        // Audit logs for important actions
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100)->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->json('payload')->nullable();
            $table->string('resource_type', 100)->nullable()->index();
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['event_type', 'created_at'], 'audit_event_created_idx');
            $table->index(['resource_type', 'resource_id'], 'audit_resource_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_generations');
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('audit_logs');
        
        // Drop added indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_active_idx');
            $table->dropIndex('users_email_idx');
        });
        
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex('subjects_user_active_idx');
            $table->dropIndex('subjects_user_name_idx');
        });
        
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('topics_subject_active_idx');
            $table->dropIndex('topics_subject_name_idx');
        });
        
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_status_deadline_idx');
            $table->dropIndex('tasks_user_priority_deadline_idx');
            $table->dropIndex('tasks_user_subject_status_idx');
            $table->dropIndex('tasks_user_topic_status_idx');
            $table->dropIndex('tasks_deadline_status_idx');
            $table->dropIndex('tasks_user_completed_idx');
        });
        
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropIndex('sessions_user_status_date_idx');
            $table->dropIndex('sessions_user_date_time_idx');
            $table->dropIndex('sessions_user_subject_status_idx');
            $table->dropIndex('sessions_task_status_idx');
            $table->dropIndex('sessions_date_status_idx');
        });
        
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('quizzes_topic_active_idx');
        });
        
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('attempts_user_status_completed_idx');
            $table->dropIndex('attempts_user_quiz_status_idx');
            $table->dropIndex('attempts_quiz_status_completed_idx');
        });
        
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropIndex('answers_attempt_question_idx');
        });
        
        Schema::table('resources', function (Blueprint $table) {
            $table->dropIndex('resources_type_active_idx');
            $table->dropIndex('resources_difficulty_active_idx');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifs_user_read_created_idx');
            $table->dropIndex('notifs_user_type_read_idx');
            $table->dropIndex('notifs_type_delivery_idx');
        });
        
        Schema::table('recommendation_views', function (Blueprint $table) {
            $table->dropIndex('rec_views_user_resource_viewed_idx');
            $table->dropIndex('rec_views_topic_viewed_idx');
        });
    }
};