<?php

namespace App\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;

class AzureFilesystemAdapter extends LaravelFilesystemAdapter
{
    /**
     * Get the public URL for the given path.
     */
    public function url($path): string
    {
        $baseUrl = $this->getConfig()['url'] ?? null;

        if (is_string($baseUrl) && $baseUrl !== '') {
            return $this->concatPathToUrl($baseUrl, $path);
        }

        return parent::url($path);
    }

    /**
     * Determine if temporary URLs can be generated.
     */
    public function providesTemporaryUrls(): bool
    {
        return method_exists($this->getAdapter(), 'temporaryUrl') || parent::providesTemporaryUrls();
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  array<string, mixed>  $options
     */
    public function temporaryUrl($path, $expiration, array $options = []): string
    {
        $adapter = $this->getAdapter();

        if (method_exists($adapter, 'temporaryUrl')) {
            return $adapter->temporaryUrl($path, $expiration, $options);
        }

        return parent::temporaryUrl($path, $expiration, $options);
    }
}
