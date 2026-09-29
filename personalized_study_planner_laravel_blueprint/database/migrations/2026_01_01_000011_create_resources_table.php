<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('resource_type', ['article','video','documentation','tutorial','ebook','website','other']);
            $table->text('url');
            $table->enum('difficulty', ['beginner','intermediate','advanced'])->default('beginner');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resource_type','is_active']);
            $table->index(['difficulty','is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('resources'); }
};
