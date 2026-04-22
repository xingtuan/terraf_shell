<?php

namespace App\Services;

use App\Enums\CommunitySubmissionPolicy;
use App\Enums\ContentStatus;
use App\Models\CommunityModerationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommunityModerationPolicyService
{
    public function getSettings(): CommunityModerationSetting
    {
        return CommunityModerationSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'submission_policy' => (string) config(
                    'community.moderation.submission_policy',
                    CommunitySubmissionPolicy::AllRequireApproval->value,
                ),
            ],
        );
    }

    public function getSubmissionPolicy(): CommunitySubmissionPolicy
    {
        $policy = $this->getSettings()->submission_policy;

        return $policy instanceof CommunitySubmissionPolicy
            ? $policy
            : CommunitySubmissionPolicy::from((string) $policy);
    }

    public function shouldAutoApprove(User $user): bool
    {
        if ($user->canModerate()) {
            return true;
        }

        return match ($this->getSubmissionPolicy()) {
            CommunitySubmissionPolicy::AllAutoApprove => true,
            CommunitySubmissionPolicy::TrustedUsersAutoApprove => (bool) $user->community_auto_approve,
            CommunitySubmissionPolicy::AllRequireApproval => false,
        };
    }

    public function statusFor(User $user): string
    {
        return $this->shouldAutoApprove($user)
            ? ContentStatus::Approved->value
            : ContentStatus::Pending->value;
    }

    public function update(string|CommunitySubmissionPolicy $policy, array $trustedUserIds = []): CommunityModerationSetting
    {
        return DB::transaction(function () use ($policy, $trustedUserIds): CommunityModerationSetting {
            $policy = $policy instanceof CommunitySubmissionPolicy
                ? $policy
                : CommunitySubmissionPolicy::from($policy);

            $settings = $this->getSettings();
            $settings->forceFill([
                'submission_policy' => $policy,
            ])->save();

            $this->syncTrustedUsers($trustedUserIds);

            return $settings->fresh();
        });
    }

    public function trustedUserIds(): array
    {
        return User::query()
            ->where('community_auto_approve', true)
            ->orderBy('name')
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }

    public function trustedUserOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->orderBy('username')
            ->get(['id', 'name', 'username'])
            ->mapWithKeys(static fn (User $user): array => [
                $user->id => sprintf('%s (@%s)', $user->name, $user->username),
            ])
            ->all();
    }

    public function syncTrustedUsers(array $trustedUserIds): void
    {
        $trustedUserIds = collect($trustedUserIds)
            ->filter(static fn (mixed $id): bool => filled($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        User::query()
            ->where('community_auto_approve', true)
            ->when(
                $trustedUserIds !== [],
                fn ($query) => $query->whereNotIn('id', $trustedUserIds),
            )
            ->update(['community_auto_approve' => false]);

        if ($trustedUserIds === []) {
            return;
        }

        User::query()
            ->whereIn('id', $trustedUserIds)
            ->update(['community_auto_approve' => true]);
    }
}
