<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('task_type', ['assignment','project','exam','quiz','reading','review','other'])->default('other');
            $table->enum('priority', ['low','medium','high','urgent'])->default('medium');
            $table->dateTime('deadline');
            $table->unsignedInteger('estimated_minutes');
            $table->enum('status', ['pending','in_progress','completed','cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'deadline']);
            $table->index(['subject_id', 'deadline']);
            $table->index(['topic_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('tasks'); }
};
