<?php

namespace App\Services\Storage;

use App\Services\Storage\Contracts\MediaStorageDriverInterface;
use App\Support\StorageUrl;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LocalMediaStorageDriver implements MediaStorageDriverInterface
{
    public function __construct(
        private readonly string $disk = 'public',
    ) {}

    public function disk(): string
    {
        return $this->disk;
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        return Storage::disk($this->disk)->put($path, $contents, $options);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function move(string $from, string $to): bool
    {
        return Storage::disk($this->disk)->move($from, $to);
    }

    public function url(string $path): string
    {
        return (string) StorageUrl::resolve($path, $this->disk);
    }

    public function publicUrl(string $path): string
    {
        return (string) StorageUrl::publicResolve($path, $this->disk);
    }

    public function temporaryUrl(string $path, ?DateTimeInterface $expiresAt = null): ?string
    {
        $storage = Storage::disk($this->disk);

        if (! method_exists($storage, 'temporaryUrl')) {
            return null;
        }

        return $storage->temporaryUrl($path, $expiresAt ?: now()->addMinutes(30));
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function testConnection(): StorageHealthResult
    {
        $path = 'health-checks/'.now()->format('YmdHis').'-local.txt';

        try {
            $storage = Storage::disk($this->disk);
            $storage->put($path, 'ok', 'public');
            $exists = $storage->exists($path);
            $storage->delete($path);

            return $exists
                ? StorageHealthResult::ok('Local storage is writable.', ['disk' => $this->disk])
                : StorageHealthResult::fail('Local storage write did not persist.', ['disk' => $this->disk]);
        } catch (Throwable $throwable) {
            return StorageHealthResult::fail($throwable->getMessage(), ['disk' => $this->disk]);
        }
    }
}
