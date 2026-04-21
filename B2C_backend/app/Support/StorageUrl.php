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

        if ($resolvedDisk === 'azure') {
            $baseUrl = self::azureBaseUrl();
            $container = trim((string) env('AZURE_STORAGE_CONTAINER', 'uploads'), '/');

            if ($baseUrl !== '') {
                return rtrim($baseUrl, '/').'/'.$container.'/'.ltrim($path, '/');
            }
        }

        return Storage::disk($resolvedDisk)->url($path);
    }

    /**
     * Resolve the Azure Blob account base URL.
     */
    public static function azureBaseUrl(): string
    {
        $configured = trim((string) env('AZURE_STORAGE_URL', ''));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $accountName = trim((string) env('AZURE_STORAGE_NAME', ''));

        return $accountName !== ''
            ? sprintf('https://%s.blob.core.windows.net', $accountName)
            : '';
    }
}
