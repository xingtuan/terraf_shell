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
}
