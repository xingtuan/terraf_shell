<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->json('name_translations')->nullable()->after('name');
        });

        DB::table('tags')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($records): void {
                foreach ($records as $record) {
                    if (is_string($record->name) && trim($record->name) !== '') {
                        DB::table('tags')->where('id', $record->id)->update([
                            'name_translations' => json_encode(
                                ['en' => $record->name],
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            ),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->dropColumn('name_translations');
        });
    }
};
