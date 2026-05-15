<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeTable('products');
        $this->normalizeTable('product_variants');
    }

    public function down(): void
    {
        //
    }

    private function normalizeTable(string $table): void
    {
        DB::table($table)
            ->whereNotNull('image_url')
            ->orderBy('id')
            ->select(['id', 'image_url', 'media_path'])
            ->chunkById(100, function ($records) use ($table): void {
                foreach ($records as $record) {
                    $imageUrl = is_string($record->image_url) ? trim($record->image_url) : '';

                    if ($imageUrl !== '' && $this->isExternalImageUrl($imageUrl)) {
                        continue;
                    }

                    $updates = ['image_url' => null];
                    $mediaPath = is_string($record->media_path) ? trim($record->media_path) : '';
                    $normalizedPath = ltrim($imageUrl, '/');

                    if ($mediaPath === '' && $normalizedPath !== '') {
                        $updates['media_path'] = $normalizedPath;
                    }

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update($updates);
                }
            });
    }

    private function isExternalImageUrl(string $value): bool
    {
        $value = strtolower(trim($value));

        return str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://');
    }
};
