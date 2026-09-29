<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total_questions');
            $table->decimal('percentage', 5, 2);
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->index(['user_id','quiz_id']);
            $table->index(['user_id','completed_at']);
            $table->index(['quiz_id','completed_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('quiz_attempts'); }
};
