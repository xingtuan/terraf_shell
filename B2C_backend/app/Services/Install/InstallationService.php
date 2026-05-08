<?php

namespace App\Services\Install;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallationService
{
    public function isInstalled(): bool
    {
        if (File::exists($this->lockPath())) {
            return true;
        }

        try {
            if (Schema::hasTable('app_settings') && app(SettingsService::class)->get('system.installed_at')) {
                return true;
            }

            return Schema::hasTable('users')
                && Schema::hasTable('app_settings')
                && User::query()->where('role', 'admin')->exists()
                && filled(app(SettingsService::class)->get('system.installed_at'));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, array{label: string, ok: bool, detail: string}>
     */
    public function requirements(): array
    {
        return [
            'php' => [
                'label' => __('admin.installer.requirements.php'),
                'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'detail' => PHP_VERSION,
            ],
            'pdo' => [
                'label' => __('admin.installer.requirements.pdo'),
                'ok' => extension_loaded('pdo'),
                'detail' => extension_loaded('pdo') ? __('admin.installer.requirements.loaded') : __('admin.installer.requirements.missing'),
            ],
            'storage' => [
                'label' => __('admin.installer.requirements.storage'),
                'ok' => is_writable(storage_path()),
                'detail' => storage_path(),
            ],
            'bootstrap_cache' => [
                'label' => __('admin.installer.requirements.bootstrap_cache'),
                'ok' => is_writable(base_path('bootstrap/cache')),
                'detail' => base_path('bootstrap/cache'),
            ],
            'env' => [
                'label' => __('admin.installer.requirements.env'),
                'ok' => File::exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path()),
                'detail' => base_path('.env'),
            ],
            'public_storage' => [
                'label' => __('admin.installer.requirements.public_storage'),
                'ok' => File::exists(public_path('storage')),
                'detail' => File::exists(public_path('storage')) ? __('admin.installer.requirements.exists') : __('admin.installer.requirements.can_create'),
            ],
        ];
    }

    public function createLock(): void
    {
        File::ensureDirectoryExists(dirname($this->lockPath()));
        File::put($this->lockPath(), now()->toISOString());
    }

    public function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }

    public function installingLockPath(): string
    {
        return storage_path('app/installing.lock');
    }

    public function isInstalling(): bool
    {
        return File::exists($this->installingLockPath());
    }

    public function createInstallingLock(): void
    {
        File::ensureDirectoryExists(dirname($this->installingLockPath()));
        File::put($this->installingLockPath(), now()->toISOString());
    }

    public function clearInstallingLock(): void
    {
        if (File::exists($this->installingLockPath())) {
            File::delete($this->installingLockPath());
        }
    }

    public function databaseIsReachable(array $data): bool
    {
        try {
            $connection = (string) ($data['db_connection'] ?? 'mysql');

            config([
                'database.default' => $connection,
                "database.connections.{$connection}.host" => $data['db_host'] ?? '127.0.0.1',
                "database.connections.{$connection}.port" => $data['db_port'] ?? '3306',
                "database.connections.{$connection}.database" => $data['db_database'] ?? '',
                "database.connections.{$connection}.username" => $data['db_username'] ?? '',
                "database.connections.{$connection}.password" => $data['db_password'] ?? '',
            ]);

            DB::purge($connection);
            DB::connection($connection)->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
