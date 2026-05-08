<?php

namespace App\Enums;

enum NotificationType: string
{
    case Comment = 'comment';
    case Reply = 'reply';
    case Like = 'like';
    case Favorite = 'favorite';
    case Follow = 'follow';
    case SubmissionApproved = 'submission_approved';
    case SubmissionRejected = 'submission_rejected';
    case ConceptFeatured = 'concept_featured';
    case SystemAnnouncement = 'system_announcement';

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
            self::Comment => __('admin.notification_type.comment'),
            self::Reply => __('admin.notification_type.reply'),
            self::Like => __('admin.notification_type.like'),
            self::Favorite => __('admin.notification_type.favorite'),
            self::Follow => __('admin.notification_type.follow'),
            self::SubmissionApproved => __('admin.notification_type.submission_approved'),
            self::SubmissionRejected => __('admin.notification_type.submission_rejected'),
            self::ConceptFeatured => __('admin.notification_type.concept_featured'),
            self::SystemAnnouncement => __('admin.notification_type.system_announcement'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Comment => 'info',
            self::Reply => 'info',
            self::Like => 'primary',
            self::Favorite => 'warning',
            self::Follow => 'success',
            self::SubmissionApproved => 'success',
            self::SubmissionRejected => 'danger',
            self::ConceptFeatured => 'warning',
            self::SystemAnnouncement => 'gray',
        };
    }
}
