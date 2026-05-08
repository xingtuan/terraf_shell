<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function __invoke(SettingsService $settings): JsonResponse
    {
        return $this->successResponse([
            'site_name' => $settings->string('app.site_name', (string) config('app.name')),
            'default_locale' => $settings->string('app.default_locale', (string) config('app.locale', 'en')),
            'supported_locales' => $settings->get('app.supported_locales', ['en', 'ko', 'zh']),
            'store_enabled' => $settings->boolean('feature.b2c_store_enabled', true),
            'b2b_inquiry_enabled' => $settings->boolean('feature.b2b_inquiry_enabled', true),
            'community_enabled' => $settings->boolean('feature.community_enabled', true),
            'guest_checkout_enabled' => $settings->boolean('feature.guest_checkout_enabled', true),
            'funding_links_enabled' => $settings->boolean('feature.funding_links_enabled', true),
            'nz_only_shipping' => $settings->boolean('shipping.nz_only', true),
            'contact_email' => $settings->string('app.contact_email', (string) config('mail.from.address')),
            'support_email' => $settings->string('app.support_email', (string) config('mail.from.address')),
            'maintenance_notice' => [
                'enabled' => $settings->boolean('maintenance.notice_enabled', $settings->boolean('feature.maintenance_notice_enabled', false)),
                'message' => $settings->string('maintenance.notice_message', ''),
                'level' => $settings->string('maintenance.notice_level', 'info'),
            ],
        ]);
    }
}
