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
        return __("admin.community_moderation.policies.{$this->value}.label");
    }

    public function helperText(): string
    {
        return __("admin.community_moderation.policies.{$this->value}.help");
    }
}
