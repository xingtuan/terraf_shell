<?php

namespace App\Filament\Support;

use App\Models\User;

class PanelAccess
{
    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function isAdmin(): bool
    {
        return self::user()?->isAdmin() ?? false;
    }

    public static function isModerator(): bool
    {
        return self::user()?->isModerator() ?? false;
    }

    public static function isStaff(): bool
    {
        return self::user()?->isStaff() ?? false;
    }
}
