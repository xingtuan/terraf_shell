<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('trending_score');
            $table->index(['status', 'views_count'], 'posts_status_views_count_index');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->index(['source_page', 'status'], 'inquiries_source_page_status_index');
        });

        DB::table('posts')->update([
            'views_count' => DB::raw('(likes_count * 12) + (comments_count * 8) + (favorites_count * 10)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex('inquiries_source_page_status_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_views_count_index');
            $table->dropColumn('views_count');
        });
    }
};
