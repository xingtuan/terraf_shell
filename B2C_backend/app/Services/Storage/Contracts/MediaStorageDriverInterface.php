<?php

namespace App\Services\Storage\Contracts;

use App\Services\Storage\StorageHealthResult;
use DateTimeInterface;

interface MediaStorageDriverInterface
{
    public function disk(): string;

    public function put(string $path, string $contents, array $options = []): bool;

    public function delete(string $path): bool;

    public function move(string $from, string $to): bool;

    public function url(string $path): string;

    public function publicUrl(string $path): string;

    public function temporaryUrl(string $path, ?DateTimeInterface $expiresAt = null): ?string;

    public function exists(string $path): bool;

    public function testConnection(): StorageHealthResult;
}
