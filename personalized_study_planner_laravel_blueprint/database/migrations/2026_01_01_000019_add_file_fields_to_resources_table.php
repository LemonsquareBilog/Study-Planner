<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('url');
            $table->string('file_url')->nullable()->after('file_path');
            $table->string('file_name')->nullable()->after('file_url');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            $table->string('file_mime_type')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn([
                'file_path',
                'file_url',
                'file_name',
                'file_size',
                'file_mime_type',
            ]);
        });
    }
};