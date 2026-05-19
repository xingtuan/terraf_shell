<?php

use App\Support\DefaultPageSections;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections') || ! Schema::hasColumn('home_sections', 'page_key')) {
            return;
        }

        $rows = DefaultPageSections::databaseRows();

        if ($rows === []) {
            return;
        }

        DB::table('home_sections')->upsert(
            $rows,
            ['page_key', 'key'],
            [
                'title',
                'title_translations',
                'subtitle',
                'subtitle_translations',
                'content',
                'content_translations',
                'cta_label',
                'cta_label_translations',
                'cta_url',
                'payload',
                'is_seeded',
                'status',
                'sort_order',
                'media_path',
                'media_url',
                'published_at',
                'updated_at',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_sections') || ! Schema::hasColumn('home_sections', 'page_key')) {
            return;
        }

        foreach (DefaultPageSections::records() as $record) {
            DB::table('home_sections')
                ->where('page_key', $record['page_key'])
                ->where('key', $record['key'])
                ->where('is_seeded', true)
                ->delete();
        }
    }
};
