<?php

namespace App\Services\Storage;

use App\Services\Settings\SettingsService;
use App\Services\Storage\Contracts\MediaStorageDriverInterface;
use DateTimeInterface;

class StorageManagerService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function active(): MediaStorageDriverInterface
    {
        return $this->driver();
    }

    public function driver(?string $driverOrDisk = null): MediaStorageDriverInterface
    {
        $driverOrDisk = trim((string) ($driverOrDisk ?: $this->settings->string(
            'storage.default_driver',
            (string) config('community.uploads.disk', 'public')
        )));

        if ($driverOrDisk === 'azure') {
            return new AzureMediaStorageDriver;
        }

        $disk = $driverOrDisk === '' || $driverOrDisk === 'local'
            ? $this->settings->string('storage.local.disk', 'public')
            : $driverOrDisk;

        return new LocalMediaStorageDriver($disk);
    }

    public function activeDriverName(): string
    {
        return $this->active()->disk() === 'azure' ? 'azure' : 'local';
    }

    public function disk(): string
    {
        return $this->active()->disk();
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        return $this->active()->put($path, $contents, $options);
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->driver($disk)->delete($path);
    }

    public function move(string $from, string $to, ?string $disk = null): bool
    {
        return $this->driver($disk)->move($from, $to);
    }

    public function url(string $path, ?string $disk = null): string
    {
        return $this->driver($disk)->url($path);
    }

    public function publicUrl(string $path, ?string $disk = null): string
    {
        return $this->driver($disk)->publicUrl($path);
    }

    public function temporaryUrl(string $path, ?DateTimeInterface $expiresAt = null, ?string $disk = null): ?string
    {
        return $this->driver($disk)->temporaryUrl($path, $expiresAt);
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->driver($disk)->exists($path);
    }

    public function test(?string $driverOrDisk = null): StorageHealthResult
    {
        return $this->driver($driverOrDisk)->testConnection();
    }
}
