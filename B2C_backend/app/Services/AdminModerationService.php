<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\ContentStatus;
use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminModerationService
{
    public function updatePostStatus(Post $post, string $status, User $admin, ?string $reason = null): Post
    {
        return DB::transaction(function () use ($post, $status, $admin, $reason): Post {
            $previousStatus = $post->status;
            $post->status = $status;

            if ($status === ContentStatus::Approved->value && $post->published_at === null) {
                $post->published_at = now();
            }

            if ($status !== ContentStatus::Approved->value) {
                $post->published_at = null;
            }

            $post->save();

            $this->log(
                $post,
                $admin,
                'post.status_updated',
                $reason,
                ['from' => $previousStatus, 'to' => $status]
            );

            return $post->fresh()->load(['user.profile', 'category', 'tags', 'images']);
        });
    }

    public function updateCommentStatus(Comment $comment, string $status, User $admin, ?string $reason = null): Comment
    {
        return DB::transaction(function () use ($comment, $status, $admin, $reason): Comment {
            $previousStatus = $comment->status;
            $comment->loadMissing('post');

            if ($previousStatus !== ContentStatus::Approved->value && $status === ContentStatus::Approved->value) {
                $comment->post->increment('comments_count');
            }

            if ($previousStatus === ContentStatus::Approved->value && $status !== ContentStatus::Approved->value) {
                $comment->post->decrement('comments_count');
            }

            $comment->status = $status;
            $comment->save();

            $this->log(
                $comment,
                $admin,
                'comment.status_updated',
                $reason,
                ['from' => $previousStatus, 'to' => $status]
            );

            return $comment->fresh()->load('user.profile');
        });
    }

    public function updateReportStatus(Report $report, string $status, User $admin, ?string $reason = null): Report
    {
        return DB::transaction(function () use ($report, $status, $admin, $reason): Report {
            $report->forceFill([
                'status' => $status,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            $this->log(
                $report,
                $admin,
                'report.status_updated',
                $reason,
                ['to' => $status]
            );

            return $report->fresh()->load(['reporter.profile', 'reviewer.profile', 'target']);
        });
    }

    public function banUser(User $target, bool $isBanned, User $admin, ?string $reason = null): User
    {
        return $this->updateAccountStatus(
            $target,
            $isBanned ? AccountStatus::Banned->value : AccountStatus::Active->value,
            $admin,
            $reason
        );
    }

    public function updateUserRole(User $target, string $role, User $admin): User
    {
        if ($admin->is($target) && $role !== $target->roleValue()) {
            throw ValidationException::withMessages([
                'user' => ['You cannot change your own role.'],
            ]);
        }

        return DB::transaction(function () use ($target, $role, $admin): User {
            $previousRole = $target->roleValue();

            $target->forceFill([
                'role' => $role,
            ])->save();

            $this->log(
                $target,
                $admin,
                'user.role_updated',
                null,
                ['from' => $previousRole, 'to' => $role]
            );

            return $target->fresh()->load('profile');
        });
    }

    public function updateAccountStatus(
        User $target,
        string $status,
        User $admin,
        ?string $reason = null
    ): User
    {
        if ($admin->is($target)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot change your own account status.'],
            ]);
        }

        return DB::transaction(function () use ($target, $status, $admin, $reason): User {
            $previousStatus = $target->accountStatusValue();

            $target->forceFill([
                'account_status' => $status,
                'is_banned' => $status === AccountStatus::Banned->value,
                'banned_at' => $status === AccountStatus::Banned->value ? now() : null,
                'ban_reason' => $status === AccountStatus::Banned->value ? $reason : null,
                'restricted_at' => $status === AccountStatus::Restricted->value ? now() : null,
                'restriction_reason' => $status === AccountStatus::Restricted->value ? $reason : null,
            ])->save();

            if ($status === AccountStatus::Banned->value) {
                $target->tokens()->delete();
            }

            $this->log(
                $target,
                $admin,
                'user.account_status_updated',
                $reason,
                ['from' => $previousStatus, 'to' => $status]
            );

            return $target->fresh()->load('profile');
        });
    }

    private function log(Model $subject, User $admin, string $action, ?string $reason = null, array $metadata = []): void
    {
        $log = new ModerationLog([
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
        $log->actor()->associate($admin);
        $log->subject()->associate($subject);
        $log->save();
    }
}
