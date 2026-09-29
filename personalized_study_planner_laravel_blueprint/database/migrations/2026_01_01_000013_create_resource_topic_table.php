<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resource_topic', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->primary(['resource_id','topic_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('resource_topic'); }
};
