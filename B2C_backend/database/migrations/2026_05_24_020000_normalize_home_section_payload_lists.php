<?php

use App\Support\HomeSectionPayloadNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections') || ! Schema::hasColumn('home_sections', 'payload')) {
            return;
        }

        DB::table('home_sections')
            ->select(['id', 'payload'])
            ->whereNotNull('payload')
            ->orderBy('id')
            ->chunkById(100, function ($sections): void {
                foreach ($sections as $section) {
                    if (! is_string($section->payload) || trim($section->payload) === '') {
                        continue;
                    }

                    $payload = json_decode($section->payload, true);

                    if (! is_array($payload)) {
                        continue;
                    }

                    $normalized = HomeSectionPayloadNormalizer::normalize($payload);

                    if ($normalized === $payload) {
                        continue;
                    }

                    DB::table('home_sections')
                        ->where('id', $section->id)
                        ->update([
                            'payload' => json_encode(
                                $normalized,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                            ),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // One-way data repair.
    }
};
