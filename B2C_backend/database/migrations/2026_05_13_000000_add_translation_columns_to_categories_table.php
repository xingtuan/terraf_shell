<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        DB::table('categories')
            ->select(['id', 'name', 'description'])
            ->orderBy('id')
            ->chunkById(100, function ($records): void {
                foreach ($records as $record) {
                    $updates = [];

                    if (is_string($record->name) && trim($record->name) !== '') {
                        $updates['name_translations'] = json_encode(
                            ['en' => $record->name],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                    }

                    if (is_string($record->description) && trim($record->description) !== '') {
                        $updates['description_translations'] = json_encode(
                            ['en' => $record->description],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                    }

                    if ($updates !== []) {
                        DB::table('categories')->where('id', $record->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['name_translations', 'description_translations']);
        });
    }
};
