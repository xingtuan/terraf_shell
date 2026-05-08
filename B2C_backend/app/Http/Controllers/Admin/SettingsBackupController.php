<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\Settings\SettingsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsBackupController extends Controller
{
    public function export(): StreamedResponse
    {
        $this->authorizeAdmin();

        $payload = [
            'exported_at' => now()->toISOString(),
            'secrets' => 'omitted',
            'settings' => AppSetting::query()
                ->where('is_secret', false)
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->mapWithKeys(fn (AppSetting $setting): array => [
                    $setting->fullKey() => app(SettingsService::class)->get($setting->fullKey()),
                ])
                ->all(),
        ];

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'terraf-oxp-settings-'.now()->format('Ymd-His').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function handoverSummary(): StreamedResponse
    {
        $this->authorizeAdmin();

        $settings = app(SettingsService::class);
        $payload = [
            'generated_at' => now()->toISOString(),
            'app' => [
                'name' => $settings->string('app.site_name', (string) config('app.name')),
                'url' => $settings->string('app.url', (string) config('app.url')),
                'frontend_url' => $settings->string('app.frontend_url', (string) config('services.frontend.url')),
                'default_locale' => $settings->string('app.default_locale', (string) config('app.locale')),
                'supported_locales' => $settings->get('app.supported_locales', ['en', 'ko', 'zh']),
            ],
            'features' => [
                'store_enabled' => $settings->boolean('feature.b2c_store_enabled', true),
                'b2b_inquiry_enabled' => $settings->boolean('feature.b2b_inquiry_enabled', true),
                'community_enabled' => $settings->boolean('feature.community_enabled', true),
                'guest_checkout_enabled' => $settings->boolean('feature.guest_checkout_enabled', true),
                'funding_links_enabled' => $settings->boolean('feature.funding_links_enabled', true),
            ],
            'storage' => [
                'active_driver' => $settings->string('storage.default_driver', 'local'),
                'last_tested_at' => $settings->string('storage.last_tested_at', ''),
                'last_test_status' => $settings->string('storage.last_test_status', ''),
                'last_test_message' => $settings->string('storage.last_test_message', ''),
            ],
            'shipping' => [
                'nz_only' => $settings->boolean('shipping.nz_only', true),
                'nzpost_enabled' => $settings->boolean('nzpost.enabled', false),
            ],
            'secrets' => 'omitted',
        ];

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'terraf-oxp-handover-settings-'.now()->format('Ymd-His').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
