<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('max_daily_minutes')->default(120);
            $table->unsignedSmallInteger('session_duration_minutes')->default(50);
            $table->unsignedSmallInteger('break_duration_minutes')->default(10);
            $table->time('preferred_start_time')->nullable();
            $table->time('preferred_end_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('study_preferences'); }
};
