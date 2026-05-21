<?php

namespace App\Support;

final class LocalStorageReadiness
{
    /**
     * @return array{disk: string, local: bool, public: bool, writable: bool, link_exists: bool, status: string, message: string}
     */
    public static function check(?string $disk = null): array
    {
        $disk = self::normalizeDisk($disk ?: (string) config('community.uploads.disk', config('filesystems.default', 'public')));
        $config = config("filesystems.disks.{$disk}", []);
        $isLocal = is_array($config) && ($config['driver'] ?? null) === 'local';
        $isPublic = $isLocal && self::isPublicLocalDisk($disk, $config);
        $writable = $isLocal ? self::isDiskWritable($config) : true;
        $linkExists = file_exists(public_path('storage'));

        $status = 'ok';
        $message = 'Storage disk is ready.';

        if ($isPublic && ! $linkExists) {
            $status = 'warning';
            $message = 'Local public storage is selected, but public/storage is not linked. Run php artisan storage:link before serving uploaded media.';
        }

        if ($isLocal && ! $writable) {
            $status = 'error';
            $message = 'Local upload directory is not writable. Check storage/app/public permissions before accepting media uploads.';
        }

        return [
            'disk' => $disk,
            'local' => $isLocal,
            'public' => $isPublic,
            'writable' => $writable,
            'link_exists' => $linkExists,
            'status' => $status,
            'message' => $message,
        ];
    }

    public static function normalizeDisk(?string $disk): string
    {
        $disk = trim((string) $disk);

        return $disk === '' || $disk === 'local' ? 'public' : $disk;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function isPublicLocalDisk(string $disk, array $config): bool
    {
        $root = (string) ($config['root'] ?? '');

        return $disk === 'public'
            || ($config['visibility'] ?? null) === 'public'
            || rtrim(str_replace('\\', '/', $root), '/') === rtrim(str_replace('\\', '/', storage_path('app/public')), '/');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function isDiskWritable(array $config): bool
    {
        $root = (string) ($config['root'] ?? storage_path('app/public'));

        if (! is_dir($root)) {
            return false;
        }

        return is_writable($root);
    }
}
