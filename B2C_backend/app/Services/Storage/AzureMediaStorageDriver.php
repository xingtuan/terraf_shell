<?php

namespace App\Services\Storage;

use App\Services\Storage\Contracts\MediaStorageDriverInterface;
use App\Support\StorageUrl;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AzureMediaStorageDriver implements MediaStorageDriverInterface
{
    public function disk(): string
    {
        return 'azure';
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        return Storage::disk($this->disk())->put($path, $contents, $options);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk())->delete($path);
    }

    public function move(string $from, string $to): bool
    {
        return Storage::disk($this->disk())->move($from, $to);
    }

    public function url(string $path): string
    {
        return (string) StorageUrl::resolve($path, $this->disk());
    }

    public function publicUrl(string $path): string
    {
        return (string) StorageUrl::publicResolve($path, $this->disk());
    }

    public function temporaryUrl(string $path, ?DateTimeInterface $expiresAt = null): ?string
    {
        try {
            return Storage::disk($this->disk())->temporaryUrl($path, $expiresAt ?: now()->addMinutes(30));
        } catch (Throwable) {
            return null;
        }
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk())->exists($path);
    }

    public function testConnection(): StorageHealthResult
    {
        if (! $this->isConfigured()) {
            return StorageHealthResult::fail('Azure storage account name, key, and container are required.');
        }

        $path = 'health-checks/'.now()->format('YmdHis').'-azure.txt';

        try {
            $storage = Storage::disk($this->disk());
            $written = $storage->put($path, 'ok', ['visibility' => 'public']);

            if ($written !== true) {
                return StorageHealthResult::fail('Azure test upload did not write to storage.', ['disk' => $this->disk()]);
            }

            $exists = $storage->exists($path);
            $storage->delete($path);

            return $exists
                ? StorageHealthResult::ok('Azure storage connection succeeded.', ['disk' => $this->disk()])
                : StorageHealthResult::fail('Azure test upload did not persist.', ['disk' => $this->disk()]);
        } catch (Throwable $throwable) {
            return StorageHealthResult::fail($throwable->getMessage(), ['disk' => $this->disk()]);
        }
    }

    private function isConfigured(): bool
    {
        return filled(config('filesystems.disks.azure.name'))
            && filled(config('filesystems.disks.azure.key'))
            && filled(config('filesystems.disks.azure.container'));
    }
}
