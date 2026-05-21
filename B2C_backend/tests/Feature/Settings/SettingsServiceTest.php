<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\LegalPageSettings;
use App\Models\AppSetting;
use App\Models\AppSettingAuditLog;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
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

    public function test_setting_changes_are_audited_without_plaintext_secrets(): void
    {
        $settings = app(SettingsService::class);
        $actor = User::factory()->create();

        $settings->set('storage.azure.account_key', 'first-secret', [
            'type' => 'string',
            'is_secret' => true,
            'updated_by' => $actor,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'SettingsServiceTest',
        ]);

        $settings->set('storage.azure.account_key', 'second-secret', [
            'type' => 'string',
            'is_secret' => true,
            'updated_by' => $actor,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'SettingsServiceTest',
        ]);

        $latest = AppSettingAuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame($actor->id, $latest->user_id);
        $this->assertSame('storage', $latest->group);
        $this->assertSame('azure.account_key', $latest->key);
        $this->assertTrue($latest->is_secret);
        $this->assertSame('[masked]', $latest->old_value);
        $this->assertSame('[masked]', $latest->new_value);
        $this->assertSame('127.0.0.1', $latest->ip_address);
        $this->assertSame('SettingsServiceTest', $latest->user_agent);

        $this->assertDatabaseMissing('app_setting_audit_logs', [
            'old_value' => 'first-secret',
        ]);
        $this->assertDatabaseMissing('app_setting_audit_logs', [
            'new_value' => 'second-secret',
        ]);
    }

    public function test_runtime_settings_pages_dehydrate_form_state_before_saving(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(LegalPageSettings::class)
            ->set('data.privacy_en_meta_title', 'Backend Privacy Meta')
            ->set('data.privacy_en_body_html', [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 2, 'onclick' => 'alert(1)'],
                        'content' => [
                            ['type' => 'text', 'text' => 'Backend body'],
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Managed in Legal Pages.'],
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(SettingsService::class);

        $this->assertSame('Backend Privacy Meta', $settings->string('legal.privacy.en.meta_title'));
        $this->assertStringContainsString('<h2>Backend body</h2>', $settings->string('legal.privacy.en.body_html'));
        $this->assertStringContainsString('<p>Managed in Legal Pages.</p>', $settings->string('legal.privacy.en.body_html'));
        $this->assertStringNotContainsString('onclick', $settings->string('legal.privacy.en.body_html'));
    }
}
