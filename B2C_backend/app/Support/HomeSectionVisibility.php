<?php

namespace App\Support;

use App\Enums\PublishStatus;

class HomeSectionVisibility
{
    public static function labelFor(mixed $value): string
    {
        return __('admin.home_sections.visibility.'.self::translationKey($value));
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            PublishStatus::Published->value => self::labelFor(PublishStatus::Published),
            PublishStatus::Draft->value => self::labelFor(PublishStatus::Draft),
            PublishStatus::Archived->value => self::labelFor(PublishStatus::Archived),
        ];
    }

    private static function translationKey(mixed $value): string
    {
        return match (PublishStatus::normalize($value)) {
            PublishStatus::Published => 'visible',
            PublishStatus::Draft => 'hidden',
            PublishStatus::Archived => 'archived',
        };
    }
}
