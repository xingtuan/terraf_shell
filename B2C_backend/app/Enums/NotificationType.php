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
    case ReportReceived = 'report_received';
    case ReportReviewed = 'report_reviewed';
    case ReportResolved = 'report_resolved';
    case ReportDismissed = 'report_dismissed';

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
            self::ReportReceived => __('admin.notification_type.report_received'),
            self::ReportReviewed => __('admin.notification_type.report_reviewed'),
            self::ReportResolved => __('admin.notification_type.report_resolved'),
            self::ReportDismissed => __('admin.notification_type.report_dismissed'),
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
            self::ReportReceived => 'info',
            self::ReportReviewed => 'info',
            self::ReportResolved => 'success',
            self::ReportDismissed => 'gray',
        };
    }
}
