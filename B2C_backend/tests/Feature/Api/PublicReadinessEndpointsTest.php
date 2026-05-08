<?php

namespace Tests\Feature\Api;

use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
