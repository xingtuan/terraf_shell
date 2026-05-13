<?php

namespace App\Enums;

enum ReportResolutionAction: string
{
    case None = 'none';
    case ContentHidden = 'content_hidden';
    case ContentRejected = 'content_rejected';
    case UserWarned = 'user_warned';
    case UserRestricted = 'user_restricted';
    case UserBanned = 'user_banned';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $action): array => [$action->value => $action->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::None => __('admin.report_resolution_action.none'),
            self::ContentHidden => __('admin.report_resolution_action.content_hidden'),
            self::ContentRejected => __('admin.report_resolution_action.content_rejected'),
            self::UserWarned => __('admin.report_resolution_action.user_warned'),
            self::UserRestricted => __('admin.report_resolution_action.user_restricted'),
            self::UserBanned => __('admin.report_resolution_action.user_banned'),
            self::Other => __('admin.report_resolution_action.other'),
        };
    }

    public function publicLabel(): string
    {
        return match ($this) {
            self::None => __('admin.report_resolution_action.public_none'),
            default => __('admin.report_resolution_action.public_action_taken'),
        };
    }
}
