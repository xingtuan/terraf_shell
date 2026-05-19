<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\EmailSetting;
use App\Services\Email\MailSettingsService;
use App\Services\Settings\SettingsService;
use App\Support\LegalPageDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DefaultAppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $settings = app(SettingsService::class);
        $mail = Schema::hasTable('email_settings')
            ? EmailSetting::query()->oldest('id')->first()
            : null;

        $uploadDisk = (string) config('community.uploads.disk', config('filesystems.default', 'public'));
        $storageDriver = $uploadDisk === 'azure' ? 'azure' : 'local';

        $defaults = [
            'app.site_name' => ['value' => config('app.name', 'Terraf OXP'), 'type' => 'string'],
            'app.admin_brand_name' => ['value' => config('app.admin_brand_name', config('app.name', 'Terraf OXP Admin')), 'type' => 'string'],
            'app.url' => ['value' => config('app.url'), 'type' => 'string'],
            'app.frontend_url' => ['value' => config('services.frontend.url'), 'type' => 'string'],
            'app.default_locale' => ['value' => config('app.locale', 'en'), 'type' => 'string'],
            'app.supported_locales' => ['value' => ['en', 'ko', 'zh'], 'type' => 'json'],
            'app.timezone' => ['value' => config('app.timezone', 'UTC'), 'type' => 'string'],
            'app.contact_email' => ['value' => config('mail.from.address'), 'type' => 'string'],
            'app.support_email' => ['value' => config('mail.from.address'), 'type' => 'string'],
            'storage.default_driver' => ['value' => $storageDriver, 'type' => 'string'],
            'storage.local.disk' => ['value' => $uploadDisk === 'azure' ? 'public' : $uploadDisk, 'type' => 'string'],
            'storage.azure.account_name' => ['value' => config('filesystems.disks.azure.name'), 'type' => 'string'],
            'storage.azure.account_key' => ['value' => config('filesystems.disks.azure.key'), 'type' => 'string', 'is_secret' => true],
            'storage.azure.container' => ['value' => config('filesystems.disks.azure.container', 'uploads'), 'type' => 'string'],
            'storage.azure.url' => ['value' => config('filesystems.disks.azure.storage_url'), 'type' => 'string'],
            'storage.azure.use_sas_urls' => ['value' => config('community.uploads.azure.use_sas_urls', true), 'type' => 'boolean'],
            'storage.azure.sas_ttl_minutes' => ['value' => config('community.uploads.azure.signed_url_ttl_minutes', 10080), 'type' => 'integer'],
            'storage.local.last_tested_at' => ['value' => null, 'type' => 'string'],
            'storage.local.last_test_status' => ['value' => null, 'type' => 'string'],
            'storage.local.last_test_message' => ['value' => null, 'type' => 'string'],
            'storage.azure.last_tested_at' => ['value' => null, 'type' => 'string'],
            'storage.azure.last_test_status' => ['value' => null, 'type' => 'string'],
            'storage.azure.last_test_message' => ['value' => null, 'type' => 'string'],
            'storage.last_tested_at' => ['value' => null, 'type' => 'string'],
            'storage.last_test_status' => ['value' => null, 'type' => 'string'],
            'storage.last_test_message' => ['value' => null, 'type' => 'string'],
            'mail.enabled' => ['value' => $mail?->is_enabled ?? false, 'type' => 'boolean'],
            'mail.mailer' => ['value' => $mail?->mailer ?? config('mail.default', 'log'), 'type' => 'string'],
            'mail.host' => ['value' => $mail?->host ?? config('mail.mailers.smtp.host'), 'type' => 'string'],
            'mail.port' => ['value' => $mail?->port ?? config('mail.mailers.smtp.port'), 'type' => 'integer'],
            'mail.username' => ['value' => $mail?->username ?? config('mail.mailers.smtp.username'), 'type' => 'string'],
            'mail.password' => ['value' => $mail?->password ?? config('mail.mailers.smtp.password'), 'type' => 'string', 'is_secret' => true],
            'mail.encryption' => [
                'value' => $mail?->encryption ?? MailSettingsService::smtpEncryptionFromTransportConfig((array) config('mail.mailers.smtp', [])),
                'type' => 'string',
            ],
            'mail.from_address' => ['value' => $mail?->from_address ?? config('mail.from.address'), 'type' => 'string'],
            'mail.from_name' => ['value' => $mail?->from_name ?? config('mail.from.name'), 'type' => 'string'],
            'community.allow_guest_upload' => ['value' => config('community.uploads.allow_guest_upload', false), 'type' => 'boolean'],
            'community.max_files' => ['value' => config('community.idea_media.max_files', 12), 'type' => 'integer'],
            'community.max_file_size_kb' => ['value' => config('community.idea_media.max_file_size_kb', 10240), 'type' => 'integer'],
            'community.allowed_extensions' => ['value' => config('community.idea_media.allowed_extensions', []), 'type' => 'json'],
            'community.max_external_links' => ['value' => config('community.idea_media.max_external_links', 4), 'type' => 'integer'],
            'community.submission_policy' => ['value' => config('community.moderation.submission_policy', 'all_require_approval'), 'type' => 'string'],
            'community.sensitive_words_enabled' => ['value' => config('community.moderation.sensitive_words.enabled', false), 'type' => 'boolean'],
            'community.sensitive_words' => ['value' => config('community.moderation.sensitive_words.terms', []), 'type' => 'json'],
            'community.default_funding_support_button_text' => ['value' => config('community.funding.default_support_button_text', 'Support this concept'), 'type' => 'string'],
            'shipping.nz_only' => ['value' => true, 'type' => 'boolean'],
            'shipping.origin_city' => ['value' => config('store.shipping.origin.city'), 'type' => 'string'],
            'shipping.origin_postcode' => ['value' => config('store.shipping.origin.postcode'), 'type' => 'string'],
            'shipping.free_shipping_threshold' => ['value' => config('store.shipping.free_shipping_threshold', 200), 'type' => 'float'],
            'shipping.fallback_standard_amount' => ['value' => config('store.shipping.standard_rate', 8), 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => config('store.shipping.express_rate', 14), 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => config('store.shipping.rural_surcharge', 5), 'type' => 'float'],
            'nzpost.enabled' => ['value' => config('store.nzpost.enabled', false), 'type' => 'boolean'],
            'nzpost.base_url' => ['value' => config('store.nzpost.base_url', 'https://api.nzpost.co.nz'), 'type' => 'string'],
            'nzpost.client_id' => ['value' => config('store.nzpost.client_id'), 'type' => 'string'],
            'nzpost.client_secret' => ['value' => config('store.nzpost.client_secret'), 'type' => 'string', 'is_secret' => true],
            'nzpost.api_key' => ['value' => config('store.nzpost.api_key'), 'type' => 'string', 'is_secret' => true],
            'tax.gst_enabled' => ['value' => true, 'type' => 'boolean'],
            'tax.gst_rate' => ['value' => config('store.tax.gst_rate', 0.15), 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => config('store.tax.prices_include_gst', true), 'type' => 'boolean'],
            'tax.label' => ['value' => config('store.tax.label', 'GST included'), 'type' => 'string'],
            'feature.b2c_store_enabled' => ['value' => true, 'type' => 'boolean'],
            'feature.b2b_inquiry_enabled' => ['value' => true, 'type' => 'boolean'],
            'feature.community_enabled' => ['value' => true, 'type' => 'boolean'],
            'feature.funding_links_enabled' => ['value' => true, 'type' => 'boolean'],
            'feature.guest_checkout_enabled' => ['value' => true, 'type' => 'boolean'],
            'feature.email_sending_enabled' => ['value' => $mail?->is_enabled ?? false, 'type' => 'boolean'],
            'feature.maintenance_notice_enabled' => ['value' => false, 'type' => 'boolean'],
            'feature.b2b_lead_notifications' => ['value' => config('community.b2b_leads.notify_admins', false), 'type' => 'boolean'],
            'maintenance.notice_enabled' => ['value' => false, 'type' => 'boolean'],
            'maintenance.notice_message' => ['value' => '', 'type' => 'string'],
            'maintenance.notice_level' => ['value' => 'info', 'type' => 'string'],
        ];

        $defaults = array_merge($defaults, LegalPageDefaults::settings());

        foreach ($defaults as $key => $payload) {
            [$group, $settingKey] = explode('.', $key, 2);
            $existing = AppSetting::query()
                ->where('group', $group)
                ->where('key', $settingKey)
                ->first();

            if ($existing) {
                if (str_starts_with($key, 'legal.') && $this->isBlankLegalSetting((string) ($existing->value ?? ''))) {
                    $settings->set($key, $payload['value'], $payload);
                }

                continue;
            }

            $settings->set($key, $payload['value'], $payload);
        }
    }

    private function isBlankLegalSetting(string $value): bool
    {
        return trim(str_ireplace('&nbsp;', ' ', strip_tags($value))) === '';
    }
}
