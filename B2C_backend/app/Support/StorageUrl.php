<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorageUrl
{
    /**
     * Resolve the public URL for a stored file path.
     */
    public static function resolve(?string $path, ?string $disk = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $resolvedDisk = (string) ($disk ?: config('community.uploads.disk', env('COMMUNITY_UPLOAD_DISK', 'azure')));

        if ($resolvedDisk === 'azure' && self::shouldUseAzureSignedUrls()) {
            return Storage::disk($resolvedDisk)->temporaryUrl(
                ltrim($path, '/'),
                now()->addMinutes(self::azureSignedUrlTtlMinutes())
            );
        }

        return self::publicResolve($path, $resolvedDisk);
    }

    /**
     * Resolve a stable public URL for a stored file path.
     */
    public static function publicResolve(?string $path, ?string $disk = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $resolvedDisk = (string) ($disk ?: config('community.uploads.disk', env('COMMUNITY_UPLOAD_DISK', 'azure')));

        if ($resolvedDisk === 'azure') {
            $baseUrl = self::azureBaseUrl();
            $container = trim((string) config('filesystems.disks.azure.container', 'uploads'), '/');

            if ($baseUrl !== '') {
                return rtrim($baseUrl, '/').'/'.$container.'/'.ltrim($path, '/');
            }
        }

        if (self::shouldServeThroughApplication($resolvedDisk)) {
            return route('media.files.show', [
                'disk' => $resolvedDisk,
                'path' => ltrim($path, '/'),
            ], true);
        }

        return Storage::disk($resolvedDisk)->url($path);
    }

    /**
     * Resolve the Azure Blob account base URL.
     */
    public static function azureBaseUrl(): string
    {
        $configured = trim((string) config('filesystems.disks.azure.storage_url', ''));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $accountName = trim((string) config('filesystems.disks.azure.name', ''));

        return $accountName !== ''
            ? sprintf('https://%s.blob.core.windows.net', $accountName)
            : '';
    }

    private static function shouldUseAzureSignedUrls(): bool
    {
        return (bool) config('community.uploads.azure.use_sas_urls', true);
    }

    private static function azureSignedUrlTtlMinutes(): int
    {
        return max(1, (int) config('community.uploads.azure.signed_url_ttl_minutes', 10080));
    }

    private static function shouldServeThroughApplication(string $disk): bool
    {
        $config = config("filesystems.disks.{$disk}");

        if (! is_array($config) || (string) ($config['driver'] ?? '') !== 'local') {
            return false;
        }

        return (string) ($config['visibility'] ?? '') === 'public'
            || $disk === (string) config('community.uploads.disk');
    }
}
