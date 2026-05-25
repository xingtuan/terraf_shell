<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class CommunityContentValidation
{
    public static function countExternalLinks(mixed $value): int
    {
        if (is_array($value)) {
            return collect($value)
                ->sum(static fn (mixed $item): int => self::countExternalLinks($item));
        }

        if (! is_scalar($value)) {
            return 0;
        }

        preg_match_all('~https?://[^\s<>"\')]+~i', strip_tags((string) $value), $matches);

        return count($matches[0] ?? []);
    }

    /**
     * @param  array<int|string, mixed>  $files
     */
    public static function countUploadedFiles(array $files): int
    {
        return collect($files)->sum(function (mixed $file): int {
            if ($file instanceof UploadedFile) {
                return 1;
            }

            return is_array($file) ? self::countUploadedFiles($file) : 0;
        });
    }
}
