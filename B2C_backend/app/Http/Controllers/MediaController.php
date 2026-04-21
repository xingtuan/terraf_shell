<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(string $disk, string $path): StreamedResponse
    {
        abort_unless($this->isResolvableLocalDisk($disk), 404);

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        abort_if(
            $normalizedPath === ''
            || str_contains($normalizedPath, '../')
            || str_contains($normalizedPath, '..\\'),
            404
        );

        $storage = Storage::disk($disk);

        abort_unless($storage->exists($normalizedPath), 404);

        return $storage->response($normalizedPath, null, [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    private function isResolvableLocalDisk(string $disk): bool
    {
        $config = config("filesystems.disks.{$disk}");

        if (! is_array($config) || (string) ($config['driver'] ?? '') !== 'local') {
            return false;
        }

        return (string) ($config['visibility'] ?? '') === 'public'
            || $disk === (string) config('community.uploads.disk');
    }
}
