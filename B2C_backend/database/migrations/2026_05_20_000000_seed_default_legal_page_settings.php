<?php

use App\Services\Settings\SettingsService;
use App\Support\LegalPageDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $now = now();

        foreach (LegalPageDefaults::settings() as $fullKey => $payload) {
            [$group, $key] = explode('.', $fullKey, 2);
            $existing = DB::table('app_settings')
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            if ($existing && ! $this->isBlankValue((string) ($existing->value ?? ''))) {
                continue;
            }

            $values = [
                'value' => $payload['value'],
                'type' => $payload['type'],
                'is_secret' => false,
                'is_encrypted' => false,
                'description' => null,
                'options' => null,
                'validation_rules' => null,
                'is_public' => $payload['is_public'],
                'updated_by' => null,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('app_settings')
                    ->where('group', $group)
                    ->where('key', $key)
                    ->update($values);

                continue;
            }

            DB::table('app_settings')->insert(array_merge($values, [
                'group' => $group,
                'key' => $key,
                'created_at' => $now,
            ]));
        }

        Cache::forget(SettingsService::CACHE_KEY);
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        foreach (LegalPageDefaults::settings() as $fullKey => $payload) {
            [$group, $key] = explode('.', $fullKey, 2);

            DB::table('app_settings')
                ->where('group', $group)
                ->where('key', $key)
                ->where('value', $payload['value'])
                ->delete();
        }

        Cache::forget(SettingsService::CACHE_KEY);
    }

    private function isBlankValue(string $value): bool
    {
        return trim(str_ireplace('&nbsp;', ' ', strip_tags($value))) === '';
    }
};
