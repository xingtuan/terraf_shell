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
        return true;
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

        $config = $this->getConfig();
        $accountName = $config['name'] ?? '';
        $accountKey = $config['key'] ?? '';
        $container = $config['container'] ?? '';
        
        if ($accountName === '' || $accountKey === '' || $container === '') {
            throw new \RuntimeException('Azure storage account name, key, and container are required to generate temporary URLs.');
        }

        $helper = new \MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper(
            $accountName, 
            $accountKey
        );

        $expirationDate = $expiration instanceof \DateTimeInterface
            ? $expiration
            : \Illuminate\Support\Carbon::parse($expiration);

        $expiry = $expirationDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        
        $queryString = $helper->generateBlobServiceSharedAccessSignatureToken(
            'b',
            $container . '/' . ltrim($path, '/'),
            'r',
            $expiry,
            '',      // start
            '',      // ip
            'https'  // protocol
        );

        $baseUrl = $config['url'] ?? null;
        
        if (is_string($baseUrl) && $baseUrl !== '') {
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/') . '?' . $queryString;
        }

        // Fallback if 'url' config is not explicitly set
        $fallbackUrl = "https://{$accountName}.blob.core.windows.net/{$container}";
        return $fallbackUrl . '/' . ltrim($path, '/') . '?' . $queryString;
    }
}
