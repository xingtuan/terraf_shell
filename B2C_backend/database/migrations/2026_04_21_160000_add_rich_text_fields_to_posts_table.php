<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            if (!Schema::hasColumn('posts', 'content_json')) {
                $table->longText('content_json')->nullable()->after('content');
            }
            if (!Schema::hasColumn('posts', 'cover_image_url')) {
                $table->string('cover_image_url', 2048)->nullable()->after('funding_url');
            }
            if (!Schema::hasColumn('posts', 'cover_image_path')) {
                $table->string('cover_image_path', 1024)->nullable()->after('cover_image_url');
            }
            if (!Schema::hasColumn('posts', 'reading_time')) {
                $table->unsignedSmallInteger('reading_time')->default(0)->after('cover_image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn([
                'content_json',
                'cover_image_url',
                'cover_image_path',
                'reading_time',
            ]);
        });
    }
};
