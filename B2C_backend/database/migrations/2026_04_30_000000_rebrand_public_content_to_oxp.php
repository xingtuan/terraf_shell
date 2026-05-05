<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->mergeBrandTag();

        $this->rebrandUniqueStringColumn('categories', 'slug');
        $this->rebrandUniqueStringColumn('product_categories', 'slug');
        $this->rebrandUniqueStringColumn('products', 'slug');
        $this->rebrandUniqueStringColumn('materials', 'slug');
        $this->rebrandUniqueStringColumn('articles', 'slug');
        $this->rebrandUniqueStringColumn('home_sections', 'key');

        $this->rebrandColumns('categories', [
            'name',
            'description',
        ]);

        $this->rebrandColumns('posts', [
            'title',
            'content',
            'content_json',
            'excerpt',
            'funding_url',
            'cover_image_url',
            'cover_image_path',
        ]);

        $this->rebrandColumns('product_categories', [
            'name',
            'name_translations',
            'description',
            'description_translations',
        ]);

        $this->rebrandColumns('products', [
            'name',
            'name_translations',
            'subtitle',
            'subtitle_translations',
            'short_description',
            'short_description_translations',
            'full_description',
            'full_description_translations',
            'features',
            'features_translations',
            'availability_text',
            'availability_text_translations',
            'sku',
            'media_path',
            'media_url',
            'image_url',
            'lead_time',
            'lead_time_translations',
            'dimensions',
            'dimensions_translations',
            'specifications',
            'certifications',
            'certifications_translations',
            'care_instructions',
            'care_instructions_translations',
            'material_benefits',
            'material_benefits_translations',
            'use_cases',
            'seo_title',
            'seo_title_translations',
            'seo_description',
            'seo_description_translations',
        ]);

        $this->rebrandColumns('product_images', [
            'alt_text',
            'alt_text_translations',
            'caption',
            'caption_translations',
            'media_path',
            'media_url',
        ]);

        $this->rebrandColumns('materials', [
            'title',
            'title_translations',
            'headline',
            'headline_translations',
            'summary',
            'summary_translations',
            'story_overview',
            'story_overview_translations',
            'science_overview',
            'science_overview_translations',
            'media_path',
            'media_url',
        ]);

        $this->rebrandColumns('material_specs', [
            'key',
            'label',
            'label_translations',
            'value',
            'value_translations',
            'detail',
            'detail_translations',
            'icon',
            'media_path',
            'media_url',
        ]);

        $this->rebrandColumns('material_story_sections', [
            'title',
            'title_translations',
            'subtitle',
            'subtitle_translations',
            'content',
            'content_translations',
            'highlight',
            'highlight_translations',
            'media_path',
            'media_url',
        ]);

        $this->rebrandColumns('material_applications', [
            'title',
            'title_translations',
            'subtitle',
            'subtitle_translations',
            'description',
            'description_translations',
            'audience',
            'audience_translations',
            'cta_label',
            'cta_label_translations',
            'cta_url',
            'media_path',
            'media_url',
        ]);

        $this->rebrandColumns('articles', [
            'title',
            'title_translations',
            'excerpt',
            'excerpt_translations',
            'content',
            'content_translations',
            'category',
            'category_translations',
            'media_path',
            'media_url',
        ]);

        $this->rebrandColumns('home_sections', [
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
            'media_path',
            'media_url',
        ]);
    }

    public function down(): void
    {
        // One-way content cleanup for the public brand rename.
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function rebrandColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column),
        ));

        if ($existingColumns === [] || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');

        DB::table($table)
            ->select(array_merge(['id'], $existingColumns))
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table, $existingColumns, $hasUpdatedAt): void {
                foreach ($records as $record) {
                    $updates = [];

                    foreach ($existingColumns as $column) {
                        $value = $record->{$column} ?? null;

                        if (! is_string($value) || $value === '') {
                            continue;
                        }

                        $nextValue = $this->replaceBrandTerms($value);

                        if ($nextValue !== $value) {
                            $updates[$column] = $nextValue;
                        }
                    }

                    if ($updates === []) {
                        continue;
                    }

                    if ($hasUpdatedAt) {
                        $updates['updated_at'] = now();
                    }

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update($updates);
                }
            });
    }

    private function rebrandUniqueStringColumn(string $table, string $column): void
    {
        if (
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, 'id') ||
            ! Schema::hasColumn($table, $column)
        ) {
            return;
        }

        $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');

        DB::table($table)
            ->select(['id', $column])
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table, $column, $hasUpdatedAt): void {
                foreach ($records as $record) {
                    $value = $record->{$column} ?? null;

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    $nextValue = $this->replaceBrandTerms($value);

                    if ($nextValue === $value) {
                        continue;
                    }

                    $nextValue = $this->uniqueValue($table, $column, $nextValue, (int) $record->id);
                    $updates = [$column => $nextValue];

                    if ($hasUpdatedAt) {
                        $updates['updated_at'] = now();
                    }

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update($updates);
                }
            });
    }

    private function uniqueValue(string $table, string $column, string $value, int $currentId): string
    {
        $candidate = $value;
        $suffix = 0;

        while (
            DB::table($table)
                ->where($column, $candidate)
                ->where('id', '!=', $currentId)
                ->exists()
        ) {
            $suffix++;
            $candidate = $value.'-'.$currentId.($suffix > 1 ? '-'.$suffix : '');
        }

        return $candidate;
    }

    private function mergeBrandTag(): void
    {
        if (
            ! Schema::hasTable('tags') ||
            ! Schema::hasColumn('tags', 'id') ||
            ! Schema::hasColumn('tags', 'slug')
        ) {
            return;
        }

        $legacySlug = $this->legacyLowerBrand();
        $legacyTag = DB::table('tags')->where('slug', $legacySlug)->first();

        if ($legacyTag === null) {
            $this->rebrandUniqueStringColumn('tags', 'slug');
            $this->rebrandUniqueStringColumn('tags', 'name');

            return;
        }

        $targetTag = DB::table('tags')
            ->where('slug', 'oxp')
            ->orWhere('name', 'oxp')
            ->first();
        $hasUpdatedAt = Schema::hasColumn('tags', 'updated_at');

        if ($targetTag === null) {
            $updates = [
                'name' => 'oxp',
                'slug' => 'oxp',
            ];

            if ($hasUpdatedAt) {
                $updates['updated_at'] = now();
            }

            DB::table('tags')
                ->where('id', $legacyTag->id)
                ->update($updates);

            return;
        }

        if ((int) $legacyTag->id === (int) $targetTag->id) {
            DB::table('tags')
                ->where('id', $targetTag->id)
                ->update($hasUpdatedAt ? ['name' => 'oxp', 'updated_at' => now()] : ['name' => 'oxp']);

            return;
        }

        if (Schema::hasTable('post_tags')) {
            $postIds = DB::table('post_tags')
                ->where('tag_id', $legacyTag->id)
                ->pluck('post_id');

            foreach ($postIds as $postId) {
                DB::table('post_tags')->updateOrInsert(
                    [
                        'post_id' => $postId,
                        'tag_id' => $targetTag->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            DB::table('post_tags')
                ->where('tag_id', $legacyTag->id)
                ->delete();
        }

        DB::table('tags')
            ->where('id', $legacyTag->id)
            ->delete();

        DB::table('tags')
            ->where('id', $targetTag->id)
            ->update($hasUpdatedAt ? ['name' => 'oxp', 'slug' => 'oxp', 'updated_at' => now()] : ['name' => 'oxp', 'slug' => 'oxp']);
    }

    private function replaceBrandTerms(string $value): string
    {
        return str_replace(
            array_keys($this->brandReplacementPairs()),
            array_values($this->brandReplacementPairs()),
            $value,
        );
    }

    /**
     * @return array<string, string>
     */
    private function brandReplacementPairs(): array
    {
        $legacyLower = $this->legacyLowerBrand();
        $legacyTitle = 'Shell'.'fin';
        $legacyUpper = 'SHELL'.'FIN';
        $legacyEmail = 'hello@'.$legacyLower.'.kr';

        return [
            'mailto:'.$legacyEmail => '#contact-form',
            $legacyEmail => 'Contact us',
            $legacyUpper => 'OXP',
            $legacyTitle => 'OXP',
            $legacyLower => 'oxp',
        ];
    }

    private function legacyLowerBrand(): string
    {
        return 'shell'.'fin';
    }
};
