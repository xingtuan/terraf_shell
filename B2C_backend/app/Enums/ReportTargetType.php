<?php

namespace App\Enums;

use App\Models\Comment;
use App\Models\Post;

enum ReportTargetType: string
{
    case Post = 'post';
    case Comment = 'comment';

    public function modelClass(): string
    {
        return match ($this) {
            self::Post => Post::class,
            self::Comment => Comment::class,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
