<?php

use App\Enums\PublishStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections') || ! Schema::hasColumn('home_sections', 'status')) {
            return;
        }

        $hasPublishedAt = Schema::hasColumn('home_sections', 'published_at');

        DB::table('home_sections')
            ->select(['id', 'status', ...($hasPublishedAt ? ['published_at'] : [])])
            ->orderBy('id')
            ->chunkById(100, function ($sections) use ($hasPublishedAt): void {
                foreach ($sections as $section) {
                    $status = PublishStatus::normalizeValue($section->status ?? null);
                    $updates = ['status' => $status];

                    if ($hasPublishedAt) {
                        $updates['published_at'] = $status === PublishStatus::Published->value
                            ? ($section->published_at ?? now())
                            : null;
                    }

                    DB::table('home_sections')
                        ->where('id', $section->id)
                        ->update($updates);
                }
            });
    }

    public function down(): void
    {
        // One-way data repair.
    }
};
