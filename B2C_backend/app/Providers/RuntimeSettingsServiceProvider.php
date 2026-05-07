<?php

namespace App\Providers;

use App\Services\Settings\SettingsService;
use Illuminate\Support\ServiceProvider;
use Throwable;

class RuntimeSettingsServiceProvider extends ServiceProvider
{
    public function boot(SettingsService $settings): void
    {
        try {
            $this->applyApplicationSettings($settings);
            $this->applyStorageSettings($settings);
            $this->applyMailSettings($settings);
            $this->applyStoreSettings($settings);
            $this->applyCommunitySettings($settings);
        } catch (Throwable) {
            // First install and early artisan commands must keep booting with env/config fallbacks.
        }
    }

    private function applyApplicationSettings(SettingsService $settings): void
    {
        $overrides = [
            'app.name' => $settings->string('app.site_name', (string) config('app.name')),
            'app.admin_brand_name' => $settings->string('app.admin_brand_name', (string) config('app.admin_brand_name', config('app.name'))),
            'app.frontend_url' => $settings->string('app.frontend_url', (string) config('services.frontend.url', '')),
            'services.frontend.url' => $settings->string('app.frontend_url', (string) config('services.frontend.url', '')),
            'app.locale' => $settings->string('app.default_locale', (string) config('app.locale', 'en')),
            'app.timezone' => $settings->string('app.timezone', (string) config('app.timezone', 'UTC')),
        ];

        if ($settings->string('app.url', '') !== '') {
            $overrides['app.url'] = $settings->string('app.url');
        }

        config($overrides);
        date_default_timezone_set((string) config('app.timezone', 'UTC'));
    }

    private function applyStorageSettings(SettingsService $settings): void
    {
        $driver = $settings->string('storage.default_driver', (string) config('community.uploads.disk', config('filesystems.default', 'public')));
        $disk = $driver === 'azure'
            ? 'azure'
            : $settings->string('storage.local.disk', $driver === 'local' ? 'public' : $driver);

        config([
            'filesystems.default' => $disk,
            'community.uploads.disk' => $disk,
            'filesystems.disks.azure.name' => $settings->string('storage.azure.account_name', (string) config('filesystems.disks.azure.name', '')),
            'filesystems.disks.azure.key' => $settings->secret('storage.azure.account_key', config('filesystems.disks.azure.key')),
            'filesystems.disks.azure.container' => $settings->string('storage.azure.container', (string) config('filesystems.disks.azure.container', 'uploads')),
            'filesystems.disks.azure.storage_url' => $settings->string('storage.azure.url', (string) config('filesystems.disks.azure.storage_url', '')),
            'community.uploads.azure.use_sas_urls' => $settings->boolean('storage.azure.use_sas_urls', (bool) config('community.uploads.azure.use_sas_urls', true)),
            'community.uploads.azure.signed_url_ttl_minutes' => $settings->integer('storage.azure.sas_ttl_minutes', (int) config('community.uploads.azure.signed_url_ttl_minutes', 10080)),
        ]);

        $azureBaseUrl = trim((string) config('filesystems.disks.azure.storage_url'));
        $container = trim((string) config('filesystems.disks.azure.container', 'uploads'), '/');

        if ($azureBaseUrl !== '') {
            config(['filesystems.disks.azure.url' => rtrim($azureBaseUrl, '/').'/'.$container]);
        }
    }

    private function applyMailSettings(SettingsService $settings): void
    {
        $mailer = $settings->string('mail.mailer', (string) config('mail.default', 'log'));

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => $settings->string('mail.host', (string) config('mail.mailers.smtp.host', '')),
            'mail.mailers.smtp.port' => $settings->integer('mail.port', (int) config('mail.mailers.smtp.port', 2525)),
            'mail.mailers.smtp.username' => $settings->string('mail.username', (string) config('mail.mailers.smtp.username', '')),
            'mail.mailers.smtp.password' => $settings->secret('mail.password', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.scheme' => $settings->string('mail.encryption', (string) config('mail.mailers.smtp.scheme', '')),
            'mail.mailers.smtp.encryption' => $settings->string('mail.encryption', (string) config('mail.mailers.smtp.encryption', '')),
            'mail.from.address' => $settings->string('mail.from_address', (string) config('mail.from.address')),
            'mail.from.name' => $settings->string('mail.from_name', (string) config('mail.from.name')),
        ]);
    }

    private function applyStoreSettings(SettingsService $settings): void
    {
        config([
            'store.shipping.nz_only' => $settings->boolean('shipping.nz_only', (bool) config('store.shipping.nz_only', true)),
            'store.shipping.origin.city' => $settings->string('shipping.origin_city', (string) config('store.shipping.origin.city', '')),
            'store.shipping.origin.postcode' => $settings->string('shipping.origin_postcode', (string) config('store.shipping.origin.postcode', '')),
            'store.shipping.free_shipping_threshold' => (float) $settings->get('shipping.free_shipping_threshold', (float) config('store.shipping.free_shipping_threshold', 200)),
            'store.shipping.standard_rate' => (float) $settings->get('shipping.fallback_standard_amount', (float) config('store.shipping.standard_rate', 8)),
            'store.shipping.express_rate' => (float) $settings->get('shipping.fallback_express_amount', (float) config('store.shipping.express_rate', 14)),
            'store.shipping.rural_surcharge' => (float) $settings->get('shipping.rural_surcharge', (float) config('store.shipping.rural_surcharge', 5)),
            'store.tax.gst_enabled' => $settings->boolean('tax.gst_enabled', (bool) config('store.tax.gst_enabled', true)),
            'store.tax.gst_rate' => (float) $settings->get('tax.gst_rate', (float) config('store.tax.gst_rate', 0.15)),
            'store.tax.prices_include_gst' => $settings->boolean('tax.prices_include_gst', (bool) config('store.tax.prices_include_gst', true)),
            'store.tax.label' => $settings->string('tax.label', (string) config('store.tax.label', 'GST included')),
            'store.nzpost.enabled' => $settings->boolean('nzpost.enabled', (bool) config('store.nzpost.enabled', false)),
            'store.nzpost.base_url' => $settings->string('nzpost.base_url', (string) config('store.nzpost.base_url', 'https://api.nzpost.co.nz')),
            'store.nzpost.client_id' => $settings->string('nzpost.client_id', (string) config('store.nzpost.client_id', '')),
            'store.nzpost.client_secret' => $settings->secret('nzpost.client_secret', config('store.nzpost.client_secret')),
            'store.nzpost.api_key' => $settings->secret('nzpost.api_key', config('store.nzpost.api_key')),
        ]);
    }

    private function applyCommunitySettings(SettingsService $settings): void
    {
        config([
            'community.uploads.allow_guest_upload' => $settings->boolean('community.allow_guest_upload', (bool) config('community.uploads.allow_guest_upload', false)),
            'community.idea_media.max_files' => $settings->integer('community.max_files', (int) config('community.idea_media.max_files', 12)),
            'community.idea_media.max_file_size_kb' => $settings->integer('community.max_file_size_kb', (int) config('community.idea_media.max_file_size_kb', 10240)),
            'community.idea_media.max_external_links' => $settings->integer('community.max_external_links', (int) config('community.idea_media.max_external_links', 4)),
            'community.idea_media.allowed_extensions' => $settings->get('community.allowed_extensions', config('community.idea_media.allowed_extensions', [])),
            'community.moderation.submission_policy' => $settings->string('community.submission_policy', (string) config('community.moderation.submission_policy', 'all_require_approval')),
            'community.moderation.sensitive_words.enabled' => $settings->boolean('community.sensitive_words_enabled', (bool) config('community.moderation.sensitive_words.enabled', false)),
            'community.moderation.sensitive_words.terms' => $settings->get('community.sensitive_words', config('community.moderation.sensitive_words.terms', [])),
            'community.b2b_leads.notify_admins' => $settings->boolean('feature.b2b_lead_notifications', (bool) config('community.b2b_leads.notify_admins', false)),
            'community.b2b_leads.notification_recipients' => $settings->get('community.b2b_lead_notification_recipients', config('community.b2b_leads.notification_recipients', [])),
            'community.funding.default_support_button_text' => $settings->string('community.default_funding_support_button_text', (string) config('community.funding.default_support_button_text', 'Support this concept')),
        ]);
    }
}
