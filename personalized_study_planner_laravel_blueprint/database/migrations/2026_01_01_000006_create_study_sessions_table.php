<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('duration_minutes');
            $table->enum('status', ['scheduled','in_progress','completed','skipped','cancelled'])->default('scheduled');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'scheduled_date']);
            $table->index(['user_id', 'status']);
            $table->index(['task_id', 'scheduled_date']);
            $table->index(['subject_id', 'scheduled_date']);
            $table->unique(['user_id','scheduled_date','start_time','end_time'], 'study_session_time_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('study_sessions'); }
};
