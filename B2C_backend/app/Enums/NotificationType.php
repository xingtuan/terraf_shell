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
            self::Comment => 'Comment',
            self::Reply => 'Reply',
            self::Like => 'Like',
            self::Favorite => 'Favorite',
            self::Follow => 'Follow',
            self::SubmissionApproved => 'Submission Approved',
            self::SubmissionRejected => 'Submission Rejected',
            self::ConceptFeatured => 'Concept Featured',
            self::SystemAnnouncement => 'System Announcement',
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
