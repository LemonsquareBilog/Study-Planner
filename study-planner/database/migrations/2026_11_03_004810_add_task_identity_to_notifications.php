<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_key')->nullable()->unique();
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropUnique(['event_key']);
            $table->dropColumn(['task_id', 'event_key', 'cancelled_at']);
        });
    }
};
