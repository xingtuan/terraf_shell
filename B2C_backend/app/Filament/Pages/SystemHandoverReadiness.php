<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\Post;
use App\Models\User;
use App\Services\Install\InstallationService;
use App\Services\Settings\SettingsService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemHandoverReadiness extends Page
{
    protected string $view = 'filament.pages.system-handover-readiness';

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'system-handover-readiness';

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.system_handover_readiness');
    }

    public function getTitle(): string
    {
        return __('admin.pages.system_handover_readiness');
    }

    /**
     * @return array<int, array{label: string, value: string, status: string, detail?: string}>
     */
    public function checks(): array
    {
        $emailSetting = Schema::hasTable('email_settings') ? EmailSetting::query()->latest('id')->first() : null;
        $uploadDisk = (string) config('community.uploads.disk', config('filesystems.default'));
        $settings = app(SettingsService::class);
        $installation = app(InstallationService::class);
        $frontendUrl = (string) config('app.frontend_url', config('services.frontend.url', ''));

        return [
            $this->row(__('admin.system.checks.app_name'), (string) config('app.name'), 'ok'),
            $this->row(__('admin.system.checks.app_url'), (string) config('app.url'), filled(config('app.url')) ? 'ok' : 'warning'),
            $this->row(__('admin.system.checks.frontend_url'), $frontendUrl, filled($frontendUrl) ? 'ok' : 'warning'),
            $this->corsSanctumRow($frontendUrl),
            $this->row(__('admin.system.checks.environment'), (string) config('app.env'), app()->environment('production') ? 'ok' : 'warning'),
            $this->databaseRow(),
            $this->storageRow($uploadDisk),
            $this->row(__('admin.system.checks.active_storage_driver'), $settings->string('storage.default_driver', $uploadDisk), 'ok'),
            $this->row(__('admin.system.checks.local_storage'), is_writable(storage_path('app/public')) ? __('admin.system.ok') : __('admin.system.failed'), is_writable(storage_path('app/public')) ? 'ok' : 'error'),
            $this->row(__('admin.system.checks.azure_storage'), $this->azureConfigured() ? __('admin.system.configured') : __('admin.system.not_configured'), $this->azureConfigured() ? 'ok' : 'warning', __('admin.system.secrets_hidden')),
            $this->row(
                __('admin.system.checks.storage_last_test'),
                $settings->string('storage.last_test_status', __('admin.system.not_available')),
                $this->statusFromSetting($settings->string('storage.last_test_status', 'warning')),
                trim(collect([
                    $settings->string('storage.last_tested_at', ''),
                    $settings->string('storage.last_test_message', ''),
                ])->filter()->implode(' | ')) ?: null,
            ),
            $this->row(__('admin.system.checks.mail_enabled'), $emailSetting?->is_enabled ? __('admin.system.enabled') : __('admin.system.disabled'), $emailSetting?->is_enabled ? 'ok' : 'warning'),
            $this->row(__('admin.system.checks.mail_provider'), (string) ($emailSetting?->mailer ?: config('mail.default')), 'ok', __('admin.system.secrets_hidden')),
            $this->mailLastTestRow(),
            $this->row(__('admin.system.checks.queue_connection'), (string) config('queue.default'), config('queue.default') === 'sync' ? 'warning' : 'ok'),
            $this->row(__('admin.system.checks.cache_driver'), (string) config('cache.default'), 'ok'),
            $this->row(__('admin.system.checks.session_driver'), (string) config('session.driver'), 'ok'),
            $this->row(__('admin.system.checks.upload_disk'), $uploadDisk, 'ok'),
            $this->row(__('admin.system.checks.storage_link'), file_exists(public_path('storage')) ? __('admin.system.yes') : __('admin.system.no'), file_exists(public_path('storage')) ? 'ok' : 'warning'),
            $this->row(__('admin.system.checks.installed_lock'), file_exists($installation->lockPath()) ? __('admin.system.yes') : __('admin.system.no'), file_exists($installation->lockPath()) ? 'ok' : 'warning'),
            $this->row(__('admin.system.checks.installing_lock'), file_exists($installation->installingLockPath()) ? __('admin.system.yes') : __('admin.system.no'), file_exists($installation->installingLockPath()) ? 'warning' : 'ok'),
            $this->row(__('admin.system.checks.key_admin'), User::query()->where('role', 'admin')->exists() ? __('admin.system.yes') : __('admin.system.no'), User::query()->where('role', 'admin')->exists() ? 'ok' : 'error'),
            $this->row(__('admin.system.checks.demo_data'), $this->demoDataExists() ? __('admin.system.yes') : __('admin.system.no'), $this->demoDataExists() ? 'warning' : 'ok'),
            $this->row(__('admin.system.checks.demo_data_count'), (string) $this->demoDataCount(), $this->demoDataCount() > 0 ? 'warning' : 'ok'),
            $this->row(__('admin.system.checks.public_settings_endpoint'), Route::has('api.public-settings') ? __('admin.system.configured') : '/api/public-settings', 'ok'),
            $this->healthRow(),
            $this->row(__('admin.system.checks.failed_jobs'), __('admin.system.failed_jobs', ['count' => $this->failedJobsCount()]), $this->failedJobsCount() > 0 ? 'error' : 'ok'),
            $this->row(__('admin.system.checks.php_version'), PHP_VERSION, version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'error'),
            $this->row(__('admin.system.checks.laravel_version'), app()->version(), 'ok'),
            $this->row(__('admin.system.checks.writable_storage'), storage_path(), is_writable(storage_path()) ? 'ok' : 'error'),
            $this->row(__('admin.system.checks.writable_bootstrap_cache'), base_path('bootstrap/cache'), is_writable(base_path('bootstrap/cache')) ? 'ok' : 'error'),
            $this->lastMigrationRow(),
        ];
    }

    /**
     * @return array{label: string, value: string, status: string, detail?: string}
     */
    private function row(string $label, string $value, string $status, ?string $detail = null): array
    {
        return array_filter([
            'label' => $label,
            'value' => $value,
            'status' => $status,
            'detail' => $detail,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * @return array{label: string, value: string, status: string, detail?: string}
     */
    private function databaseRow(): array
    {
        try {
            DB::select('select 1');

            return $this->row(__('admin.system.checks.database'), __('admin.system.connected'), 'ok', (string) config('database.default'));
        } catch (Throwable $throwable) {
            return $this->row(__('admin.system.checks.database'), __('admin.system.failed'), 'error', $throwable->getMessage());
        }
    }

    /**
     * @return array{label: string, value: string, status: string, detail?: string}
     */
    private function storageRow(string $disk): array
    {
        try {
            Storage::disk($disk);

            return $this->row(__('admin.system.checks.storage_disk'), __('admin.system.configured'), 'ok', $disk);
        } catch (Throwable $throwable) {
            return $this->row(__('admin.system.checks.storage_disk'), __('admin.system.failed'), 'error', $throwable->getMessage());
        }
    }

    private function demoDataExists(): bool
    {
        return $this->demoDataCount() > 0;
    }

    private function demoDataCount(): int
    {
        return Schema::hasColumn('posts', 'is_demo_content')
            ? (int) Post::query()->where('is_demo_content', true)->count()
            : 0;
    }

    private function failedJobsCount(): int
    {
        return Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
    }

    private function azureConfigured(): bool
    {
        return filled(config('filesystems.disks.azure.name'))
            && filled(config('filesystems.disks.azure.key'))
            && filled(config('filesystems.disks.azure.container'));
    }

    private function statusFromSetting(string $status): string
    {
        return in_array($status, ['ok', 'warning', 'error'], true) ? $status : 'warning';
    }

    private function corsSanctumRow(string $frontendUrl): array
    {
        $cors = collect(config('cors.allowed_origins', []))->filter()->implode(', ');
        $sanctum = collect(config('sanctum.stateful', []))->filter()->implode(', ');
        $status = 'ok';

        if (filled($frontendUrl)) {
            $host = parse_url($frontendUrl, PHP_URL_HOST);
            $corsMatches = $host && Str::contains($cors, $host);
            $sanctumMatches = $host && Str::contains($sanctum, $host);
            $status = $corsMatches && $sanctumMatches ? 'ok' : 'warning';
        }

        return $this->row(
            __('admin.system.checks.cors_sanctum'),
            $status === 'ok' ? __('admin.system.ok') : __('admin.system.warning'),
            $status,
            trim(__('admin.system.cors_sanctum_detail', ['cors' => $cors ?: '-', 'sanctum' => $sanctum ?: '-'])),
        );
    }

    private function mailLastTestRow(): array
    {
        if (! Schema::hasTable('email_logs')) {
            return $this->row(__('admin.system.checks.mail_last_test'), __('admin.system.not_available'), 'warning');
        }

        $log = EmailLog::query()
            ->where('event_key', 'admin.test_email')
            ->latest('id')
            ->first();

        if (! $log instanceof EmailLog) {
            return $this->row(__('admin.system.checks.mail_last_test'), __('admin.system.not_available'), 'warning');
        }

        return $this->row(
            __('admin.system.checks.mail_last_test'),
            (string) $log->status,
            $log->status === EmailLog::STATUS_FAILED ? 'error' : 'ok',
            $log->created_at?->toDateTimeString(),
        );
    }

    private function healthRow(): array
    {
        return $this->row(
            __('admin.system.checks.health_check'),
            '/api/health',
            $this->databaseRow()['status'] === 'ok' && $this->storageRow((string) config('community.uploads.disk', config('filesystems.default')))['status'] === 'ok'
                ? 'ok'
                : 'warning',
        );
    }

    /**
     * @return array{label: string, value: string, status: string, detail?: string}
     */
    private function lastMigrationRow(): array
    {
        if (! Schema::hasTable('migrations')) {
            return $this->row(__('admin.system.checks.last_migration'), __('admin.system.not_available'), 'warning');
        }

        $migration = DB::table('migrations')
            ->orderByDesc('batch')
            ->orderByDesc('id')
            ->first();

        return $this->row(
            __('admin.system.checks.last_migration'),
            $migration?->migration ?: __('admin.system.not_available'),
            $migration ? 'ok' : 'warning',
            $migration ? __('admin.system.batch', ['batch' => $migration->batch]) : null,
        );
    }
}
