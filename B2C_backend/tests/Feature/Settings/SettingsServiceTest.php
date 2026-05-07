<?php

namespace Tests\Feature\Settings;

use App\Models\AppSetting;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_string_boolean_and_integer_settings(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('app.site_name', 'OXP Runtime', ['type' => 'string']);
        $settings->set('feature.community_enabled', true, ['type' => 'boolean']);
        $settings->set('community.max_files', 7, ['type' => 'integer']);

        $this->assertSame('OXP Runtime', $settings->string('app.site_name'));
        $this->assertTrue($settings->boolean('feature.community_enabled'));
        $this->assertSame(7, $settings->integer('community.max_files'));
    }

    public function test_can_save_encrypted_secret_without_plaintext_storage(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('storage.azure.account_key', 'super-secret-key', [
            'type' => 'string',
            'is_secret' => true,
        ]);

        $record = AppSetting::query()
            ->where('group', 'storage')
            ->where('key', 'azure.account_key')
            ->firstOrFail();

        $this->assertTrue($record->is_secret);
        $this->assertTrue($record->is_encrypted);
        $this->assertNotSame('super-secret-key', $record->getRawOriginal('value'));
        $this->assertSame('super-secret-key', $settings->secret('storage.azure.account_key'));
    }

    public function test_cache_clears_after_update(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('app.site_name', 'Before', ['type' => 'string']);
        $this->assertSame('Before', $settings->string('app.site_name'));

        Cache::put(SettingsService::CACHE_KEY, collect([
            'app.site_name' => [
                'group' => 'app',
                'key' => 'site_name',
                'value' => 'Stale',
                'type' => 'string',
                'is_secret' => false,
                'is_encrypted' => false,
                'is_public' => false,
            ],
        ]));

        $settings->set('app.site_name', 'After', ['type' => 'string']);

        $this->assertSame('After', $settings->string('app.site_name'));
    }
}
