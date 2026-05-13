<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\ContentStatus;
use App\Enums\ReportResolutionAction;
use App\Enums\ReportStatus;
use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserViolation;
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

    public function updatePostStatus(
        Post $post,
        string $status,
        User $admin,
        ?string $reason = null,
        ?Report $report = null
    ): Post {
        return DB::transaction(function () use ($post, $status, $admin, $reason, $report): Post {
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
            $this->log($post, $admin, 'post.status_updated', $reason, $metadata, $post->user, $report);
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
                        $post,
                        $report
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
                        $post,
                        $report
                    );
                }
            }

            return $post->fresh()->load(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign']);
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

            return $post->fresh()->load(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign']);
        });
    }

    public function updateCommentStatus(
        Comment $comment,
        string $status,
        User $admin,
        ?string $reason = null,
        ?Report $report = null
    ): Comment {
        return DB::transaction(function () use ($comment, $status, $admin, $reason, $report): Comment {
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
            $this->log($comment, $admin, 'comment.status_updated', $reason, $metadata, $comment->user, $report);
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
                    $comment,
                    $report
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
                    $comment,
                    $report
                );
            }

            return $comment->fresh()->load('user.profile');
        });
    }

    public function updateReportStatus(Report $report, string $status, User $admin, ?string $reason = null): Report
    {
        return match ($status) {
            ReportStatus::Reviewed->value => $this->markReportReviewed($report, $admin, $reason, null),
            ReportStatus::Dismissed->value => $this->dismissReport($report, $admin, $reason, null),
            ReportStatus::Resolved->value => $this->resolveReport(
                $report,
                $admin,
                ReportResolutionAction::Other->value,
                $reason,
                null
            ),
            default => throw ValidationException::withMessages([
                'status' => ['Reports can only be reviewed, resolved, or dismissed through moderation actions.'],
            ]),
        };
    }

    public function markReportReviewed(
        Report $report,
        User $admin,
        ?string $internalNote,
        ?string $publicNote
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeReviewed($report);

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Reviewed,
                ReportResolutionAction::None,
                $internalNote,
                $publicNote,
                'report.reviewed',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportReviewed($updated, $admin);
                }
            );
        });
    }

    public function dismissReport(
        Report $report,
        User $admin,
        ?string $internalNote,
        ?string $publicNote
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Dismissed,
                ReportResolutionAction::None,
                $internalNote,
                $publicNote,
                'report.dismissed',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportDismissed($updated, $admin);
                }
            );
        });
    }

    public function resolveReport(
        Report $report,
        User $admin,
        ?string $resolutionAction,
        ?string $internalNote,
        ?string $publicNote
    ): Report {
        $action = $this->normalizeResolutionAction($resolutionAction);

        return DB::transaction(function () use ($report, $admin, $action, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Resolved,
                $action,
                $internalNote,
                $publicNote,
                'report.resolved',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportResolved($updated, $admin);
                }
            );
        });
    }

    public function resolveReportAndHideTarget(
        Report $report,
        User $admin,
        ?string $internalNote = null,
        ?string $publicNote = null
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);
            $this->updateReportedContentStatus($report, ContentStatus::Hidden->value, $admin, $internalNote);

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Resolved,
                ReportResolutionAction::ContentHidden,
                $internalNote,
                $publicNote,
                'report.resolved_target_hidden',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportResolved($updated, $admin);
                }
            );
        });
    }

    public function resolveReportAndRejectTarget(
        Report $report,
        User $admin,
        ?string $internalNote = null,
        ?string $publicNote = null
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);
            $this->updateReportedContentStatus($report, ContentStatus::Rejected->value, $admin, $internalNote);

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Resolved,
                ReportResolutionAction::ContentRejected,
                $internalNote,
                $publicNote,
                'report.resolved_target_rejected',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportResolved($updated, $admin);
                }
            );
        });
    }

    public function resolveReportAndWarnUser(
        Report $report,
        User $admin,
        ?string $internalNote = null,
        ?string $publicNote = null
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);
            $target = $this->reportTarget($report);
            $targetUser = $this->reportTargetUser($report);

            $this->governanceService->createViolation(
                $targetUser,
                $admin,
                UserViolationType::ManualWarning->value,
                UserViolationSeverity::Warning->value,
                $internalNote,
                ['report_id' => $report->id],
                $target,
                $report
            );

            $metadata = ['report_id' => $report->id, 'type' => UserViolationType::ManualWarning->value];
            $this->log($target, $admin, 'user.warned_from_report', $internalNote, $metadata, $targetUser, $report);
            $this->recordAdminAction($admin, 'user.warned_from_report', $internalNote, $metadata, $target, $targetUser);

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Resolved,
                ReportResolutionAction::UserWarned,
                $internalNote,
                $publicNote,
                'report.resolved_user_warned',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportResolved($updated, $admin);
                }
            );
        });
    }

    public function resolveReportAndRestrictUser(
        Report $report,
        User $admin,
        ?string $internalNote = null,
        ?string $publicNote = null
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);
            $this->updateAccountStatus(
                $this->reportTargetUser($report),
                AccountStatus::Restricted->value,
                $admin,
                $internalNote,
                $report
            );

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Resolved,
                ReportResolutionAction::UserRestricted,
                $internalNote,
                $publicNote,
                'report.resolved_user_restricted',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportResolved($updated, $admin);
                }
            );
        });
    }

    public function resolveReportAndBanUser(
        Report $report,
        User $admin,
        ?string $internalNote = null,
        ?string $publicNote = null
    ): Report {
        return DB::transaction(function () use ($report, $admin, $internalNote, $publicNote): Report {
            $this->assertReportCanBeFinalized($report);
            $this->updateAccountStatus(
                $this->reportTargetUser($report),
                AccountStatus::Banned->value,
                $admin,
                $internalNote,
                $report
            );

            return $this->applyReportDisposition(
                $report,
                $admin,
                ReportStatus::Resolved,
                ReportResolutionAction::UserBanned,
                $internalNote,
                $publicNote,
                'report.resolved_user_banned',
                function (Report $updated) use ($admin): void {
                    $this->notificationService->notifyReportResolved($updated, $admin);
                }
            );
        });
    }

    public function banUser(
        User $target,
        bool $isBanned,
        User $admin,
        ?string $reason = null,
        ?Report $report = null
    ): User {
        return $this->updateAccountStatus(
            $target,
            $isBanned ? AccountStatus::Banned->value : AccountStatus::Active->value,
            $admin,
            $reason,
            $report
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
        ?string $reason = null,
        ?Report $report = null
    ): User {
        if (AccountStatus::tryFrom($status) === null) {
            throw ValidationException::withMessages([
                'account_status' => ['The selected account status is invalid.'],
            ]);
        }

        if ($admin->is($target)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot change your own account status.'],
            ]);
        }

        return DB::transaction(function () use ($target, $status, $admin, $reason, $report): User {
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
            $this->log($target, $admin, 'user.account_status_updated', $reason, $metadata, $target, $report);
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
                        $reason ?: 'Account returned to active.'
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
                        $target,
                        $report
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
                        $target,
                        $report
                    );
                }
            }

            return $target->fresh()->load('profile');
        });
    }

    public function resolveAccountViolationAndRestore(
        UserViolation $violation,
        User $admin,
        string $resolutionNote
    ): UserViolation {
        if ($violation->status !== UserViolationStatus::Open->value || ! in_array($violation->type, [
            UserViolationType::AccountBanned->value,
            UserViolationType::AccountRestricted->value,
        ], true)) {
            throw ValidationException::withMessages([
                'violation' => ['Only open account ban or restriction violations can restore an account.'],
            ]);
        }

        return DB::transaction(function () use ($violation, $admin, $resolutionNote): UserViolation {
            $violation->loadMissing('user');

            $this->governanceService->updateViolationStatus(
                $violation,
                $admin,
                UserViolationStatus::Resolved->value,
                $resolutionNote
            );

            $this->governanceService->resolveViolations(
                $violation->user,
                [
                    'types' => [
                        UserViolationType::AccountBanned->value,
                        UserViolationType::AccountRestricted->value,
                    ],
                ],
                $admin,
                $resolutionNote
            );

            $this->updateAccountStatus(
                $violation->user,
                AccountStatus::Active->value,
                $admin,
                $resolutionNote
            );

            return $violation->fresh()->load(['user.profile', 'actor.profile', 'resolver.profile', 'subject', 'report']);
        });
    }

    private function applyReportDisposition(
        Report $report,
        User $admin,
        ReportStatus $status,
        ReportResolutionAction $resolutionAction,
        ?string $internalNote,
        ?string $publicNote,
        string $logAction,
        callable $notifyReporter
    ): Report {
        $report->loadMissing('target');
        $targetUser = $this->governanceService->subjectOwner($report);
        $timestamp = now();
        $isFinalStatus = in_array($status, [ReportStatus::Resolved, ReportStatus::Dismissed], true);

        $attributes = [
            'status' => $status->value,
            'moderator_note' => $internalNote,
            'public_note' => $publicNote,
            'reviewed_by' => $admin->id,
            'reviewed_at' => $status === ReportStatus::Reviewed ? $timestamp : ($report->reviewed_at ?? $timestamp),
            'resolved_at' => $status === ReportStatus::Resolved ? $timestamp : null,
            'dismissed_at' => $status === ReportStatus::Dismissed ? $timestamp : null,
            'completed_at' => $isFinalStatus ? $timestamp : null,
            'resolution_action' => $resolutionAction->value,
        ];

        $report->forceFill($attributes)->save();

        $metadata = [
            'status' => $status->value,
            'resolution_action' => $resolutionAction->value,
        ];

        $this->log($report, $admin, $logAction, $internalNote, $metadata, $targetUser, $report);
        $this->recordAdminAction($admin, $logAction, $internalNote, $metadata, $report, $targetUser);

        $updatedReport = $report->fresh()->load(['reporter.profile', 'reviewer.profile', 'target']);
        $notifyReporter($updatedReport);
        $updatedReport->forceFill([
            'reporter_notified_at' => now(),
        ])->save();

        return $updatedReport->fresh()->load(['reporter.profile', 'reviewer.profile', 'target']);
    }

    private function assertReportCanBeReviewed(Report $report): void
    {
        if ($report->status !== ReportStatus::Pending->value) {
            throw ValidationException::withMessages([
                'status' => ['Only pending reports can be marked as reviewed.'],
            ]);
        }
    }

    private function assertReportCanBeFinalized(Report $report): void
    {
        if ($report->isFinalized()) {
            throw ValidationException::withMessages([
                'status' => ['This report has already been completed.'],
            ]);
        }

        if (! $report->isOpenForModeration()) {
            throw ValidationException::withMessages([
                'status' => ['Only pending or reviewed reports can be completed.'],
            ]);
        }
    }

    private function normalizeResolutionAction(?string $resolutionAction): ReportResolutionAction
    {
        $value = trim((string) $resolutionAction);

        if ($value === '') {
            return ReportResolutionAction::Other;
        }

        $action = ReportResolutionAction::tryFrom($value);

        if ($action === null) {
            throw ValidationException::withMessages([
                'resolution_action' => ['The selected report resolution action is invalid.'],
            ]);
        }

        return $action;
    }

    private function updateReportedContentStatus(
        Report $report,
        string $status,
        User $admin,
        ?string $internalNote
    ): void {
        $target = $this->reportTarget($report);

        match (true) {
            $target instanceof Post => $this->updatePostStatus($target, $status, $admin, $internalNote, $report),
            $target instanceof Comment => $this->updateCommentStatus($target, $status, $admin, $internalNote, $report),
            default => throw ValidationException::withMessages([
                'target' => ['This report target cannot be moderated as content.'],
            ]),
        };
    }

    private function reportTarget(Report $report): Model
    {
        $report->loadMissing('target');

        if (! $report->target instanceof Model) {
            throw ValidationException::withMessages([
                'target' => ['The report target is no longer available.'],
            ]);
        }

        return $report->target;
    }

    private function reportTargetUser(Report $report): User
    {
        $targetUser = $this->governanceService->subjectOwner($report);

        if (! $targetUser instanceof User) {
            throw ValidationException::withMessages([
                'target_user' => ['The reported user could not be determined.'],
            ]);
        }

        return $targetUser;
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
