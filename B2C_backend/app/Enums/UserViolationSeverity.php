<?php

namespace App\Enums;

enum UserViolationSeverity: string
{
    case Notice = 'notice';
    case Warning = 'warning';
    case Restriction = 'restriction';
    case Ban = 'ban';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $severity): array => [$severity->value => $severity->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Notice => __('admin.violation_severity.notice'),
            self::Warning => __('admin.violation_severity.warning'),
            self::Restriction => __('admin.violation_severity.restriction'),
            self::Ban => __('admin.violation_severity.ban'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Notice => 'gray',
            self::Warning => 'warning',
            self::Restriction => 'danger',
            self::Ban => 'danger',
        };
    }
}
