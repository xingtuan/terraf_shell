<?php

namespace Tests\Feature\Settings;

use App\Providers\RuntimeSettingsServiceProvider;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RuntimeSettingsServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_settings_override_config_values(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('storage.default_driver', 'local', ['type' => 'string']);
        $settings->set('storage.local.disk', 'public', ['type' => 'string']);
        $settings->set('storage.azure.account_name', 'runtimeaccount', ['type' => 'string']);
        $settings->set('mail.mailer', 'smtp', ['type' => 'string']);
        $settings->set('mail.host', 'smtp.runtime.test', ['type' => 'string']);
        $settings->set('tax.gst_rate', 0.2, ['type' => 'float']);

        (new RuntimeSettingsServiceProvider(app()))->boot($settings);

        $this->assertSame('public', config('community.uploads.disk'));
        $this->assertSame('runtimeaccount', config('filesystems.disks.azure.name'));
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.runtime.test', config('mail.mailers.smtp.host'));
        $this->assertSame(0.2, config('store.tax.gst_rate'));
    }

    public function test_provider_does_not_break_when_app_settings_table_is_missing(): void
    {
        Schema::drop('app_settings');

        try {
            (new RuntimeSettingsServiceProvider(app()))->boot(app(SettingsService::class));

            $this->assertTrue(true);
        } finally {
            $migration = include database_path('migrations/2026_05_08_000000_create_app_settings_table.php');
            $migration->up();
        }
    }
}
