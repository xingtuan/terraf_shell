<?php

namespace App\Enums;

enum CommunitySubmissionPolicy: string
{
    case AllRequireApproval = 'all_require_approval';
    case TrustedUsersAutoApprove = 'trusted_users_auto_approve';
    case AllAutoApprove = 'all_auto_approve';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $policy): array => [$policy->value => $policy->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::AllRequireApproval => 'All content requires approval',
            self::TrustedUsersAutoApprove => 'Trusted users are approved automatically',
            self::AllAutoApprove => 'Approve all submissions automatically',
        };
    }

    public function helperText(): string
    {
        return match ($this) {
            self::AllRequireApproval => 'Every new post and comment is queued for moderation.',
            self::TrustedUsersAutoApprove => 'Only designated users can bypass moderation automatically.',
            self::AllAutoApprove => 'All community posts and comments go live immediately.',
        };
    }
}
