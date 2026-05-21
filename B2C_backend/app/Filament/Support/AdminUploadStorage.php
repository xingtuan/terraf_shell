<?php

namespace App\Filament\Support;

use App\Services\Storage\StorageManagerService;

class AdminUploadStorage
{
    public static function disk(): string
    {
        $disk = trim(app(StorageManagerService::class)->disk());

        return $disk === '' || $disk === 'local' ? 'public' : $disk;
    }

    public static function visibility(): string
    {
        return self::disk() === 'azure' ? 'private' : 'public';
    }
}
