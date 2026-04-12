<?php

namespace App\Enums;

enum UserViolationType: string
{
    case ManualWarning = 'manual_warning';
    case SensitiveWord = 'sensitive_word';
    case ContentRejected = 'content_rejected';
    case ContentHidden = 'content_hidden';
    case AccountRestricted = 'account_restricted';
    case AccountBanned = 'account_banned';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
