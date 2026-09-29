<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['task_reminder','deadline_reminder','study_session_reminder','recommendation','system']);
            $table->string('title', 200);
            $table->text('message');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('delivery_status', ['pending','sent','failed'])->default('pending');
            $table->timestamps();
            $table->index(['user_id','read_at']);
            $table->index(['user_id','scheduled_at']);
            $table->index(['delivery_status','scheduled_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('notifications'); }
};
