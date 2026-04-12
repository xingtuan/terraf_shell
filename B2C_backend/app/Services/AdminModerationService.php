<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\ContentStatus;
use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminModerationService
{
    public function __construct(
        private readonly PostRankingService $postRankingService,
        private readonly NotificationService $notificationService,
        private readonly GovernanceService $governanceService,
    ) {}

    public function updatePostStatus(Post $post, string $status, User $admin, ?string $reason = null): Post
    {
        return DB::transaction(function () use ($post, $status, $admin, $reason): Post {
            $previousStatus = $post->status;
            $post->loadMissing('user');
            $post->status = $status;

            if ($status === ContentStatus::Approved->value && $post->published_at === null) {
                $post->published_at = now();
            }

            if ($status !== ContentStatus::Approved->value) {
                $post->published_at = null;
            }

            $post->save();

            $metadata = ['from' => $previousStatus, 'to' => $status];
            $this->log($post, $admin, 'post.status_updated', $reason, $metadata, $post->user);
            $this->recordAdminAction($admin, 'post.status_updated', $reason, $metadata, $post, $post->user);

            $post = $this->postRankingService->refreshScores($post);

            if ($previousStatus !== $status) {
                if ($status === ContentStatus::Approved->value) {
                    $this->governanceService->resolveViolations(
                        $post->user,
                        [
                            'types' => [
                                UserViolationType::ContentRejected->value,
                                UserViolationType::ContentHidden->value,
                            ],
                            'subject' => $post,
                        ],
                        $admin,
                        'Concept approved during moderation review.'
                    );
                    $this->notificationService->notifyPostApproved($post, $admin);
                }

                if ($status === ContentStatus::Rejected->value) {
                    $this->governanceService->resolveViolations(
                        $post->user,
                        [
                            'types' => [UserViolationType::ContentHidden->value],
                            'subject' => $post,
                        ],
                        $admin,
                        'Hidden record superseded by rejection.'
                    );
                    $this->governanceService->createViolation(
                        $post->user,
                        $admin,
                        UserViolationType::ContentRejected->value,
                        UserViolationSeverity::Warning->value,
                        $reason,
                        $metadata,
                        $post
                    );
                    $this->notificationService->notifyPostRejected($post, $admin, $reason);
                }

                if ($status === ContentStatus::Hidden->value) {
                    $this->governanceService->resolveViolations(
                        $post->user,
                        [
                            'types' => [UserViolationType::ContentRejected->value],
                            'subject' => $post,
                        ],
                        $admin,
                        'Rejected record superseded by takedown.'
                    );
                    $this->governanceService->createViolation(
                        $post->user,
                        $admin,
                        UserViolationType::ContentHidden->value,
                        UserViolationSeverity::Warning->value,
                        $reason,
                        $metadata,
                        $post
                    );
                }
            }

            return $post->fresh()->load(['user.profile', 'category', 'tags', 'images', 'media']);
        });
    }

    public function updatePostFeaturedStatus(
        Post $post,
        bool $isFeatured,
        User $admin,
        ?string $reason = null
    ): Post {
        return DB::transaction(function () use ($post, $isFeatured, $admin, $reason): Post {
            $previousState = (bool) $post->is_featured;
            $post->loadMissing('user');
            $post = $this->postRankingService->markFeatured($post, $isFeatured, $admin);

            $metadata = ['from' => $previousState, 'to' => $isFeatured];
            $this->log($post, $admin, 'post.feature_updated', $reason, $metadata, $post->user);
            $this->recordAdminAction($admin, 'post.feature_updated', $reason, $metadata, $post, $post->user);

            if (! $previousState && $isFeatured) {
                $this->notificationService->notifyPostFeatured($post, $admin);
            }

            return $post->fresh()->load(['user.profile', 'category', 'tags', 'images', 'media']);
        });
    }

    public function updateCommentStatus(Comment $comment, string $status, User $admin, ?string $reason = null): Comment
    {
        return DB::transaction(function () use ($comment, $status, $admin, $reason): Comment {
            $previousStatus = $comment->status;
            $comment->loadMissing(['post', 'user', 'parent.user']);

            if ($previousStatus !== ContentStatus::Approved->value && $status === ContentStatus::Approved->value) {
                $comment->post->increment('comments_count');
            }

            if ($previousStatus === ContentStatus::Approved->value && $status !== ContentStatus::Approved->value) {
                $comment->post->decrement('comments_count');
            }

            $comment->status = $status;
            $comment->save();
            $this->postRankingService->refreshScores($comment->post->fresh());

            $metadata = ['from' => $previousStatus, 'to' => $status];
            $this->log($comment, $admin, 'comment.status_updated', $reason, $metadata, $comment->user);
            $this->recordAdminAction($admin, 'comment.status_updated', $reason, $metadata, $comment, $comment->user);

            if ($previousStatus !== ContentStatus::Approved->value && $status === ContentStatus::Approved->value) {
                $this->governanceService->resolveViolations(
                    $comment->user,
                    [
                        'types' => [
                            UserViolationType::ContentRejected->value,
                            UserViolationType::ContentHidden->value,
                        ],
                        'subject' => $comment,
                    ],
                    $admin,
                    'Comment approved during moderation review.'
                );

                if ($comment->parent_id !== null && $comment->parent !== null) {
                    $this->notificationService->notifyReplyCreated($comment->parent, $comment, $admin);
                } else {
                    $this->notificationService->notifyCommentCreated($comment->post, $comment, $admin);
                }
            }

            if ($previousStatus !== $status && $status === ContentStatus::Rejected->value) {
                $this->governanceService->resolveViolations(
                    $comment->user,
                    [
                        'types' => [UserViolationType::ContentHidden->value],
                        'subject' => $comment,
                    ],
                    $admin,
                    'Hidden record superseded by rejection.'
                );
                $this->governanceService->createViolation(
                    $comment->user,
                    $admin,
                    UserViolationType::ContentRejected->value,
                    UserViolationSeverity::Warning->value,
                    $reason,
                    $metadata,
                    $comment
                );
            }

            if ($previousStatus !== $status && $status === ContentStatus::Hidden->value) {
                $this->governanceService->resolveViolations(
                    $comment->user,
                    [
                        'types' => [UserViolationType::ContentRejected->value],
                        'subject' => $comment,
                    ],
                    $admin,
                    'Rejected record superseded by takedown.'
                );
                $this->governanceService->createViolation(
                    $comment->user,
                    $admin,
                    UserViolationType::ContentHidden->value,
                    UserViolationSeverity::Warning->value,
                    $reason,
                    $metadata,
                    $comment
                );
            }

            return $comment->fresh()->load('user.profile');
        });
    }

    public function updateReportStatus(Report $report, string $status, User $admin, ?string $reason = null): Report
    {
        return DB::transaction(function () use ($report, $status, $admin, $reason): Report {
            $report->loadMissing('target');
            $targetUser = $this->governanceService->subjectOwner($report);
            $report->forceFill([
                'status' => $status,
                'moderator_note' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            $metadata = ['to' => $status];
            $this->log($report, $admin, 'report.status_updated', $reason, $metadata, $targetUser, $report);
            $this->recordAdminAction($admin, 'report.status_updated', $reason, $metadata, $report, $targetUser);

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

            $metadata = ['from' => $previousRole, 'to' => $role];
            $this->log($target, $admin, 'user.role_updated', null, $metadata, $target);
            $this->recordAdminAction($admin, 'user.role_updated', null, $metadata, $target, $target);

            return $target->fresh()->load('profile');
        });
    }

    public function updateAccountStatus(
        User $target,
        string $status,
        User $admin,
        ?string $reason = null
    ): User {
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

            $metadata = ['from' => $previousStatus, 'to' => $status];
            $this->log($target, $admin, 'user.account_status_updated', $reason, $metadata, $target);
            $this->recordAdminAction($admin, 'user.account_status_updated', $reason, $metadata, $target, $target);

            if ($previousStatus !== $status) {
                if ($status === AccountStatus::Active->value) {
                    $this->governanceService->resolveViolations(
                        $target,
                        [
                            'types' => [
                                UserViolationType::AccountRestricted->value,
                                UserViolationType::AccountBanned->value,
                            ],
                        ],
                        $admin,
                        'Account returned to active.'
                    );
                }

                if ($status === AccountStatus::Restricted->value) {
                    $this->governanceService->resolveViolations(
                        $target,
                        [
                            'types' => [UserViolationType::AccountBanned->value],
                        ],
                        $admin,
                        'Ban record superseded by restriction.'
                    );
                    $this->governanceService->createViolation(
                        $target,
                        $admin,
                        UserViolationType::AccountRestricted->value,
                        UserViolationSeverity::Restriction->value,
                        $reason,
                        $metadata,
                        $target
                    );
                }

                if ($status === AccountStatus::Banned->value) {
                    $this->governanceService->resolveViolations(
                        $target,
                        [
                            'types' => [UserViolationType::AccountRestricted->value],
                        ],
                        $admin,
                        'Restriction record superseded by ban.'
                    );
                    $this->governanceService->createViolation(
                        $target,
                        $admin,
                        UserViolationType::AccountBanned->value,
                        UserViolationSeverity::Ban->value,
                        $reason,
                        $metadata,
                        $target
                    );
                }
            }

            return $target->fresh()->load('profile');
        });
    }

    private function log(
        Model $subject,
        User $admin,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?User $targetUser = null,
        ?Report $report = null
    ): void {
        $this->governanceService->recordModerationLog(
            $subject,
            $admin,
            $action,
            $reason,
            $metadata,
            $targetUser,
            $report
        );
    }

    private function recordAdminAction(
        User $admin,
        string $action,
        ?string $description = null,
        array $metadata = [],
        ?Model $subject = null,
        ?User $targetUser = null
    ): void {
        $this->governanceService->recordAdminAction(
            $admin,
            $action,
            $description,
            $metadata,
            $subject,
            $targetUser
        );
    }
}
