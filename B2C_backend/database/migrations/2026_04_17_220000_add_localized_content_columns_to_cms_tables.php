<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->json('title_translations')->nullable();
            $table->json('headline_translations')->nullable();
            $table->json('summary_translations')->nullable();
            $table->json('story_overview_translations')->nullable();
            $table->json('science_overview_translations')->nullable();
        });

        Schema::table('material_specs', function (Blueprint $table): void {
            $table->json('label_translations')->nullable();
            $table->json('value_translations')->nullable();
            $table->json('detail_translations')->nullable();
        });

        Schema::table('material_story_sections', function (Blueprint $table): void {
            $table->json('title_translations')->nullable();
            $table->json('subtitle_translations')->nullable();
            $table->json('content_translations')->nullable();
            $table->json('highlight_translations')->nullable();
        });

        Schema::table('material_applications', function (Blueprint $table): void {
            $table->json('title_translations')->nullable();
            $table->json('subtitle_translations')->nullable();
            $table->json('description_translations')->nullable();
            $table->json('audience_translations')->nullable();
            $table->json('cta_label_translations')->nullable();
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->json('title_translations')->nullable();
            $table->json('excerpt_translations')->nullable();
            $table->json('content_translations')->nullable();
            $table->json('category_translations')->nullable();
        });

        Schema::table('home_sections', function (Blueprint $table): void {
            $table->json('title_translations')->nullable();
            $table->json('subtitle_translations')->nullable();
            $table->json('content_translations')->nullable();
            $table->json('cta_label_translations')->nullable();
        });

        $this->backfillTranslations('materials', [
            'title',
            'headline',
            'summary',
            'story_overview',
            'science_overview',
        ]);
        $this->backfillTranslations('material_specs', [
            'label',
            'value',
            'detail',
        ]);
        $this->backfillTranslations('material_story_sections', [
            'title',
            'subtitle',
            'content',
            'highlight',
        ]);
        $this->backfillTranslations('material_applications', [
            'title',
            'subtitle',
            'description',
            'audience',
            'cta_label',
        ]);
        $this->backfillTranslations('articles', [
            'title',
            'excerpt',
            'content',
            'category',
        ]);
        $this->backfillTranslations('home_sections', [
            'title',
            'subtitle',
            'content',
            'cta_label',
        ]);
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table): void {
            $table->dropColumn([
                'title_translations',
                'subtitle_translations',
                'content_translations',
                'cta_label_translations',
            ]);
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn([
                'title_translations',
                'excerpt_translations',
                'content_translations',
                'category_translations',
            ]);
        });

        Schema::table('material_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'title_translations',
                'subtitle_translations',
                'description_translations',
                'audience_translations',
                'cta_label_translations',
            ]);
        });

        Schema::table('material_story_sections', function (Blueprint $table): void {
            $table->dropColumn([
                'title_translations',
                'subtitle_translations',
                'content_translations',
                'highlight_translations',
            ]);
        });

        Schema::table('material_specs', function (Blueprint $table): void {
            $table->dropColumn([
                'label_translations',
                'value_translations',
                'detail_translations',
            ]);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn([
                'title_translations',
                'headline_translations',
                'summary_translations',
                'story_overview_translations',
                'science_overview_translations',
            ]);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function backfillTranslations(string $table, array $columns): void
    {
        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table, $columns): void {
                foreach ($records as $record) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $record->{$column} ?? null;

                        if (! is_string($value) || trim($value) === '') {
                            continue;
                        }

                        $updates[$column.'_translations'] = json_encode(
                            ['en' => $value],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                    }

                    if ($updates !== []) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update($updates);
                    }
                }
            });
    }
};
