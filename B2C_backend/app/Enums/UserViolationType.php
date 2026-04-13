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

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::ManualWarning => 'Manual Warning',
            self::SensitiveWord => 'Sensitive Word',
            self::ContentRejected => 'Content Rejected',
            self::ContentHidden => 'Content Hidden',
            self::AccountRestricted => 'Account Restricted',
            self::AccountBanned => 'Account Banned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ManualWarning => 'gray',
            self::SensitiveWord => 'warning',
            self::ContentRejected => 'danger',
            self::ContentHidden => 'warning',
            self::AccountRestricted => 'warning',
            self::AccountBanned => 'danger',
        };
    }
}
