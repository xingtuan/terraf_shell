<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funding_campaigns', function (Blueprint $table): void {
            $table->json('support_button_text_translations')->nullable()->after('support_button_text');
            $table->json('reward_description_translations')->nullable()->after('reward_description');
        });

        DB::table('funding_campaigns')
            ->select(['id', 'support_button_text', 'reward_description'])
            ->orderBy('id')
            ->chunkById(100, function ($records): void {
                foreach ($records as $record) {
                    $updates = [];

                    if (is_string($record->support_button_text) && trim($record->support_button_text) !== '') {
                        $updates['support_button_text_translations'] = json_encode(
                            ['en' => $record->support_button_text],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                    }

                    if (is_string($record->reward_description) && trim($record->reward_description) !== '') {
                        $updates['reward_description_translations'] = json_encode(
                            ['en' => $record->reward_description],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                    }

                    if ($updates !== []) {
                        DB::table('funding_campaigns')->where('id', $record->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('funding_campaigns', function (Blueprint $table): void {
            $table->dropColumn(['support_button_text_translations', 'reward_description_translations']);
        });
    }
};
