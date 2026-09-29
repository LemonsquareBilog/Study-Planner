<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->text('description')->nullable();
            $table->string('label')->nullable();
            $table->string('group')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->text('impact_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('system_settings'); }
};
