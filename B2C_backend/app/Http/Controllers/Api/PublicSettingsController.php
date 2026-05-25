<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommunitySettingsService;
use App\Services\Settings\SettingsService;
use App\Support\StorageUrl;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function __invoke(SettingsService $settings, CommunitySettingsService $communitySettings): JsonResponse
    {
        $siteName = $settings->string('app.site_name', (string) config('app.name', 'OXP'));
        $logoPath = $settings->string('app.logo_path', '');
        $logoDisk = $settings->string('app.logo_disk', '');
        $logoUrl = filled($logoPath) ? StorageUrl::publicResolve($logoPath, filled($logoDisk) ? $logoDisk : null) : null;

        return $this->successResponse([
            'site_name' => $siteName,
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
            'branding' => [
                'logo_url' => $logoUrl,
                'logo_text' => $siteName,
                'logo_alt' => $siteName,
            ],
            'maintenance_mode' => [
                'enabled' => $settings->boolean('maintenance.mode_enabled', false),
            ],
            'maintenance_notice' => [
                'enabled' => $settings->boolean('maintenance.notice_enabled', $settings->boolean('feature.maintenance_notice_enabled', false)),
                'message_en' => $settings->string('maintenance.notice_message_en', $settings->string('maintenance.notice_message', '')),
                'message_ko' => $settings->string('maintenance.notice_message_ko', ''),
                'message_zh' => $settings->string('maintenance.notice_message_zh', ''),
                'level' => $settings->string('maintenance.notice_level', 'info'),
            ],
            'community' => [
                'allow_guest_upload' => $communitySettings->allowGuestUpload(),
                'max_files' => $communitySettings->maxFiles(),
                'max_file_size_kb' => $communitySettings->maxFileSizeKb(),
                'allowed_extensions' => $communitySettings->allowedExtensions(),
                'max_external_links' => $communitySettings->maxExternalLinks(),
                'submission_policy' => $communitySettings->submissionPolicy(),
                'sensitive_words_enabled' => $communitySettings->sensitiveWordsEnabled(),
                'default_funding_support_button_text' => $communitySettings->defaultFundingSupportButtonText(),
            ],
        ]);
    }
}
