<?php

use App\Enums\PublishStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        if (
            DB::table('home_sections')
                ->where('key', 'pilot_projects')
                ->exists()
        ) {
            return;
        }

        $now = now();

        DB::table('home_sections')->insert([
            'key' => 'pilot_projects',
            'title' => 'Pilot projects and collaborations',
            'title_translations' => $this->json([
                'en' => 'Pilot projects and collaborations',
            ]),
            'subtitle' => 'Pilot projects',
            'subtitle_translations' => $this->json([
                'en' => 'Pilot projects',
            ]),
            'content' => 'Client, partner, and case-study names are shown only after approval. Until then, transparent placeholders keep the site credible.',
            'content_translations' => $this->json([
                'en' => 'Client, partner, and case-study names are shown only after approval. Until then, transparent placeholders keep the site credible.',
            ]),
            'cta_label' => null,
            'cta_label_translations' => null,
            'cta_url' => null,
            'payload' => $this->json([
                'variant' => 'pilot_projects',
                'items' => [
                    $this->pilotProjectItem(
                        'Pilot collaboration details coming soon',
                        'Coming soon',
                        'Project details will be added after client approval.'
                    ),
                    $this->pilotProjectItem(
                        'University and industry review in progress',
                        'In testing',
                        'Research or review partners can be named after confirmation.'
                    ),
                    $this->pilotProjectItem(
                        'Client-provided case studies pending approval',
                        'Client confirmation pending',
                        'Case studies remain hidden or generic until publication is approved.'
                    ),
                ],
            ]),
            'is_seeded' => true,
            'status' => PublishStatus::Published->value,
            'sort_order' => 11,
            'media_path' => null,
            'media_url' => null,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        DB::table('home_sections')
            ->where('key', 'pilot_projects')
            ->where('is_seeded', true)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function pilotProjectItem(string $title, string $status, string $description): array
    {
        return [
            'title' => $title,
            'title_translations' => [
                'en' => $title,
            ],
            'status' => $status,
            'status_translations' => [
                'en' => $status,
            ],
            'description' => $description,
            'description_translations' => [
                'en' => $description,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
};
