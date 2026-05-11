<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'materials',
        'material_specs',
        'material_story_sections',
        'material_applications',
        'articles',
        'home_sections',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('is_seeded')->default(false)->index();
            });
        }

        $this->backfillKnownSeededRecords();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('is_seeded');
            });
        }
    }

    private function backfillKnownSeededRecords(): void
    {
        DB::table('materials')
            ->where('slug', 'premium-oyster-shell')
            ->update(['is_seeded' => true]);

        $materialId = DB::table('materials')
            ->where('slug', 'premium-oyster-shell')
            ->value('id');

        if ($materialId !== null) {
            DB::table('material_specs')
                ->where('material_id', $materialId)
                ->whereIn('key', [
                    'weight',
                    'strength',
                    'flexibility',
                    'absorption',
                    'surface',
                    'circularity',
                ])
                ->update(['is_seeded' => true]);

            DB::table('material_story_sections')
                ->where('material_id', $materialId)
                ->whereIn('title', [
                    'Shell collection',
                    'Cleaning and thermal purification',
                    'Pellet compounding',
                    'Compression moulding and finishing',
                ])
                ->update(['is_seeded' => true]);

            DB::table('material_applications')
                ->where('material_id', $materialId)
                ->whereIn('title', [
                    'Hospitality and tabletop',
                    'Premium interior objects',
                    'Retail and brand installations',
                    'B2B pellet supply and co-development',
                ])
                ->update(['is_seeded' => true]);
        }

        DB::table('articles')
            ->whereIn('slug', [
                'material-platform-launch',
                'science-notes-shell-composite',
            ])
            ->update(['is_seeded' => true]);

        DB::table('home_sections')
            ->whereIn('key', [
                'hero',
                'science_block',
                'latest_updates',
            ])
            ->update(['is_seeded' => true]);
    }
};
