<?php

namespace App\Enums;

enum B2BLeadStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Contacted = 'contacted';
    case SampleSent = 'sample_sent';
    case InDiscussion = 'in_discussion';
    case Archived = 'archived';
    case Qualified = 'qualified';
    case FollowUp = 'follow_up';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public static function enquiryValues(): array
    {
        return [
            self::New->value,
            self::InReview->value,
            self::Contacted->value,
            self::SampleSent->value,
            self::InDiscussion->value,
            self::FollowUp->value,
            self::Resolved->value,
            self::Closed->value,
            self::Archived->value,
        ];
    }

    public static function enquiryOptions(): array
    {
        return collect(self::enquiryValues())
            ->mapWithKeys(fn (string $status): array => [$status => self::from($status)->label()])
            ->all();
    }

    public function label(): string
    {
        return __("admin.leads.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::InReview => 'warning',
            self::Contacted => 'info',
            self::SampleSent => 'success',
            self::InDiscussion => 'primary',
            self::Archived => 'gray',
            self::Qualified => 'success',
            self::FollowUp => 'warning',
            self::Resolved => 'success',
            self::Closed => 'primary',
        };
    }
}
