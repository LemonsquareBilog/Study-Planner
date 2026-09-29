<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('question');
            $table->string('option_a', 500);
            $table->string('option_b', 500);
            $table->string('option_c', 500);
            $table->string('option_d', 500);
            $table->enum('correct_answer', ['A','B','C','D']);
            $table->unsignedSmallInteger('question_order');
            $table->timestamps();
            $table->unique(['quiz_id','question_order'], 'quiz_question_order_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('questions'); }
};
