<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->uuid('submission_id')->nullable();
            $table->string('submission_hash', 64)->nullable();
            $table->unique(['user_id', 'submission_id']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'submission_id']);
            $table->dropColumn(['submission_id', 'submission_hash']);
        });
    }
};
