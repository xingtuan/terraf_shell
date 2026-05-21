<?php

namespace Tests\Feature\Api;

use App\Services\Settings\SettingsService;
use App\Support\LegalPageDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicReadinessEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_returns_only_safe_runtime_values(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('app.site_name', 'Terraf OXP Demo', ['type' => 'string', 'is_public' => true]);
        $settings->set('feature.guest_checkout_enabled', true, ['type' => 'boolean', 'is_public' => true]);
        $settings->set('storage.azure.account_key', 'never-export-this', ['type' => 'string', 'is_secret' => true]);
        $settings->set('mail.password', 'smtp-secret', ['type' => 'string', 'is_secret' => true]);

        $response = $this->getJson('/api/public-settings')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.site_name', 'Terraf OXP Demo')
            ->assertJsonPath('data.guest_checkout_enabled', true);

        $json = $response->getContent();

        $this->assertStringNotContainsString('never-export-this', $json);
        $this->assertStringNotContainsString('smtp-secret', $json);
        $this->assertStringNotContainsString('account_key', $json);
        $this->assertStringNotContainsString('password', $json);
    }

    public function test_health_endpoints_return_safe_status_payloads(): void
    {
        foreach (['/api/health', '/api/health/database', '/api/health/storage', '/api/health/mail'] as $endpoint) {
            $this->getJson($endpoint)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonMissingPath('data.path')
                ->assertJsonMissingPath('data.secret');
        }
    }

    public function test_legal_page_endpoint_returns_admin_content_for_requested_locale(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('legal.privacy.zh.meta_title', 'Backend Privacy Meta', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.privacy.zh.title', 'Backend Privacy Title', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.privacy.zh.description', 'Backend privacy summary.', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.privacy.zh.last_updated', 'May 2026', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.privacy.zh.body_html', '<h2>Backend body</h2><p>Managed in Legal Pages.</p>', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.privacy.en.title', 'English Privacy Title', ['type' => 'string', 'is_public' => true]);

        $this->getJson('/api/legal-pages/privacy?locale=zh')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metaTitle', 'Backend Privacy Meta')
            ->assertJsonPath('data.title', 'Backend Privacy Title')
            ->assertJsonPath('data.description', 'Backend privacy summary.')
            ->assertJsonPath('data.lastUpdated', 'May 2026')
            ->assertJsonPath('data.bodyHtml', '<h2>Backend body</h2><p>Managed in Legal Pages.</p>');
    }

    public function test_legal_page_endpoint_keeps_privacy_and_terms_separate(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('legal.privacy.en.title', 'Backend Privacy Title', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.terms.en.title', 'Backend Terms Title', ['type' => 'string', 'is_public' => true]);

        $this->getJson('/api/legal-pages/privacy?locale=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Backend Privacy Title');

        $this->getJson('/api/legal-pages/terms?locale=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Backend Terms Title');
    }

    public function test_legal_page_endpoint_omits_blank_body_html(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('legal.terms.en.title', 'Backend Terms Title', ['type' => 'string', 'is_public' => true]);
        $settings->set('legal.terms.en.body_html', '<p>&nbsp;</p>', ['type' => 'string', 'is_public' => true]);

        $this->getJson('/api/legal-pages/terms?locale=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Backend Terms Title')
            ->assertJsonMissingPath('data.bodyHtml');
    }

    public function test_legal_page_endpoint_sanitizes_admin_html(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('legal.privacy.en.title', 'Backend Privacy Title', ['type' => 'string', 'is_public' => true]);
        $settings->set(
            'legal.privacy.en.body_html',
            '<script>alert(1)</script><p onclick="alert(2)">Safe <strong>formatting</strong></p><a href="javascript:alert(3)" onclick="alert(4)">Bad link</a><a href="https://example.com">Good link</a>',
            ['type' => 'string', 'is_public' => true]
        );

        $body = (string) $this->getJson('/api/legal-pages/privacy?locale=en')
            ->assertOk()
            ->json('data.bodyHtml');

        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertStringContainsString('<p>Safe <strong>formatting</strong></p>', $body);
        $this->assertStringContainsString('href="https://example.com"', $body);
        $this->assertStringContainsString('rel="noopener noreferrer"', $body);
    }

    public function test_default_legal_page_examples_are_available_for_public_locales(): void
    {
        app(SettingsService::class)->forgetCache();

        foreach (['privacy', 'terms'] as $page) {
            foreach (['en', 'ko', 'zh'] as $locale) {
                $data = $this->getJson("/api/legal-pages/{$page}?locale={$locale}")
                    ->assertOk()
                    ->assertJsonPath('success', true)
                    ->json('data');

                $this->assertNotEmpty($data['metaTitle'] ?? null);
                $this->assertNotEmpty($data['title'] ?? null);
                $this->assertNotEmpty($data['description'] ?? null);
                $this->assertNotEmpty($data['lastUpdatedLabel'] ?? null);
                $this->assertNotEmpty($data['lastUpdated'] ?? null);
                $this->assertNotEmpty($data['bodyHtml'] ?? null);
            }
        }
    }

    public function test_default_legal_page_migration_fills_blank_values_without_overwriting_edits(): void
    {
        DB::table('app_settings')
            ->where('group', 'legal')
            ->where('key', 'privacy.zh.meta_title')
            ->update(['value' => '']);

        DB::table('app_settings')
            ->where('group', 'legal')
            ->where('key', 'privacy.zh.title')
            ->update(['value' => 'Custom legal title']);

        DB::table('app_settings')
            ->where('group', 'legal')
            ->where('key', 'terms.en.body_html')
            ->update(['value' => '<p>&nbsp;</p>']);

        $migration = include database_path('migrations/2026_05_20_000000_seed_default_legal_page_settings.php');
        $migration->up();

        app(SettingsService::class)->forgetCache();
        $settings = app(SettingsService::class);
        $defaults = LegalPageDefaults::settings();

        $this->assertSame($defaults['legal.privacy.zh.meta_title']['value'], $settings->string('legal.privacy.zh.meta_title'));
        $this->assertSame('Custom legal title', $settings->string('legal.privacy.zh.title'));
        $this->assertSame($defaults['legal.terms.en.body_html']['value'], $settings->string('legal.terms.en.body_html'));
    }
}
