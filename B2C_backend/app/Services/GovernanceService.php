<?php

namespace App\Services;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use App\Models\AdminActionLog;
use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserViolation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GovernanceService
{
    public function __construct(
        private readonly SensitiveContentService $sensitiveContentService,
    ) {}

    public function recordModerationLog(
        Model $subject,
        ?User $actor,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?User $targetUser = null,
        ?Report $report = null,
    ): ModerationLog {
        $targetUser ??= $this->resolveTargetUser($subject);
        $reportId = $report?->id;

        if ($subject instanceof Report && $reportId === null) {
            $reportId = $subject->id;
        }

        return ModerationLog::query()->create([
            'actor_user_id' => $actor?->id,
            'target_user_id' => $targetUser?->id,
            'report_id' => $reportId,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    public function recordAdminAction(
        ?User $actor,
        string $action,
        ?string $description = null,
        array $metadata = [],
        ?Model $subject = null,
        ?User $targetUser = null,
    ): AdminActionLog {
        $targetUser ??= $subject !== null ? $this->resolveTargetUser($subject) : null;

        return AdminActionLog::query()->create([
            'actor_user_id' => $actor?->id,
            'target_user_id' => $targetUser?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function createViolation(
        User $user,
        ?User $actor,
        string $type,
        string $severity,
        ?string $reason = null,
        array $metadata = [],
        ?Model $subject = null,
        ?Report $report = null,
        string $status = UserViolationStatus::Open->value,
    ): UserViolation {
        return UserViolation::query()->create([
            'user_id' => $user->id,
            'actor_user_id' => $actor?->id,
            'report_id' => $report?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'type' => $type,
            'severity' => $severity,
            'status' => $status,
            'reason' => $reason,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }

    public function storeManualViolation(User $user, User $actor, array $data): UserViolation
    {
        return DB::transaction(function () use ($user, $actor, $data): UserViolation {
            $subject = $this->resolveSubject($data['subject_type'] ?? null, $data['subject_id'] ?? null);
            $report = isset($data['report_id']) ? Report::query()->find($data['report_id']) : null;

            if (($data['subject_type'] ?? null) !== null && $subject === null) {
                throw ValidationException::withMessages([
                    'subject_id' => ['The selected subject could not be found.'],
                ]);
            }

            if ($subject !== null) {
                $this->assertSubjectBelongsToUser($user, $subject);
            }

            if ($report !== null) {
                $this->assertReportAffectsUser($user, $report);
            }

            $violation = $this->createViolation(
                $user,
                $actor,
                $data['type'],
                $data['severity'],
                $data['reason'] ?? null,
                $data['metadata'] ?? [],
                $subject,
                $report
            );

            $auditSubject = $subject ?? $user;
            $metadata = array_filter([
                'violation_id' => $violation->id,
                'type' => $violation->type,
                'severity' => $violation->severity,
                'report_id' => $report?->id,
            ]);

            $this->recordModerationLog(
                $auditSubject,
                $actor,
                'user.violation_recorded',
                $violation->reason,
                $metadata,
                $user,
                $report
            );

            $this->recordAdminAction(
                $actor,
                'user.violation_recorded',
                $violation->reason,
                $metadata,
                $auditSubject,
                $user
            );

            return $violation->fresh()->load(['user.profile', 'actor.profile', 'resolver.profile', 'subject', 'report']);
        });
    }

    public function updateViolationStatus(
        UserViolation $violation,
        User $actor,
        string $status,
        ?string $resolutionNote = null
    ): UserViolation {
        return DB::transaction(function () use ($violation, $actor, $status, $resolutionNote): UserViolation {
            $previousStatus = $violation->status;
            $violation->forceFill([
                'status' => $status,
                'resolved_by' => $status === UserViolationStatus::Resolved->value ? $actor->id : null,
                'resolved_at' => $status === UserViolationStatus::Resolved->value ? now() : null,
                'resolution_note' => $status === UserViolationStatus::Resolved->value ? $resolutionNote : null,
            ])->save();

            $auditSubject = $violation->subject ?? $violation->user;
            $metadata = [
                'violation_id' => $violation->id,
                'from' => $previousStatus,
                'to' => $status,
                'type' => $violation->type,
            ];

            $this->recordModerationLog(
                $auditSubject,
                $actor,
                'user.violation_status_updated',
                $resolutionNote,
                $metadata,
                $violation->user,
                $violation->report
            );

            $this->recordAdminAction(
                $actor,
                'user.violation_status_updated',
                $resolutionNote,
                $metadata,
                $auditSubject,
                $violation->user
            );

            return $violation->fresh()->load(['user.profile', 'actor.profile', 'resolver.profile', 'subject', 'report']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function resolveViolations(
        User $user,
        array $filters = [],
        ?User $actor = null,
        ?string $resolutionNote = null
    ): int {
        $query = UserViolation::query()
            ->where('user_id', $user->id)
            ->where('status', UserViolationStatus::Open->value);

        if (! empty($filters['types'])) {
            $query->whereIn('type', (array) $filters['types']);
        }

        if (($filters['subject'] ?? null) instanceof Model) {
            $subject = $filters['subject'];
            $query
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey());
        }

        if (($filters['report'] ?? null) instanceof Report) {
            $query->where('report_id', $filters['report']->id);
        }

        return $query->update([
            'status' => UserViolationStatus::Resolved->value,
            'resolved_by' => $actor?->id,
            'resolved_at' => now(),
            'resolution_note' => $resolutionNote,
            'updated_at' => now(),
        ]);
    }

    public function flagSensitiveContent(
        User $user,
        array $fields,
        string $actionPrefix,
        ?Model $subject = null,
        ?Report $report = null,
    ): ?UserViolation {
        if ($user->isStaff()) {
            return null;
        }

        $matches = $this->sensitiveContentService->scan($fields);

        if ($matches['matched_terms'] === []) {
            return null;
        }

        $reason = 'Sensitive language detected and queued for moderation review.';
        $violation = $this->createViolation(
            $user,
            null,
            UserViolationType::SensitiveWord->value,
            UserViolationSeverity::Warning->value,
            $reason,
            [
                'matched_terms' => $matches['matched_terms'],
                'matched_fields' => $matches['matched_fields'],
                'source' => $actionPrefix,
            ],
            $subject,
            $report
        );

        $this->recordModerationLog(
            $subject ?? $report ?? $user,
            $user,
            $actionPrefix.'.sensitive_word_flagged',
            $reason,
            [
                'violation_id' => $violation->id,
                'matched_terms' => $matches['matched_terms'],
                'matched_fields' => $matches['matched_fields'],
            ],
            $user,
            $report
        );

        return $violation;
    }

    public function listUserViolations(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = UserViolation::query()
            ->where('user_id', $user->id)
            ->with(['user.profile', 'actor.profile', 'resolver.profile', 'subject', 'report'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($this->perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function listModerationHistory(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = ModerationLog::query()
            ->where('target_user_id', $user->id)
            ->with(['actor.profile', 'targetUser.profile', 'subject', 'report'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query->paginate($this->perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function listAdminActionsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = AdminActionLog::query()
            ->where('target_user_id', $user->id)
            ->with(['actor.profile', 'targetUser.profile', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query->paginate($this->perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function listReviewHistory(Model $subject, array $filters = []): LengthAwarePaginator
    {
        $query = ModerationLog::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->with(['actor.profile', 'targetUser.profile', 'subject', 'report'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query->paginate($this->perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function resolveSubject(?string $type, null|int|string $id): ?Model
    {
        if ($type === null || $id === null) {
            return null;
        }

        $numericId = (int) $id;

        return match ($type) {
            'post' => Post::query()->find($numericId),
            'comment' => Comment::query()->find($numericId),
            'user' => User::query()->find($numericId),
            default => null,
        };
    }

    public function subjectOwner(?Model $subject): ?User
    {
        return $this->resolveTargetUser($subject);
    }

    private function resolveTargetUser(?Model $subject): ?User
    {
        return match (true) {
            $subject instanceof User => $subject,
            $subject instanceof Post => $subject->loadMissing('user')->user,
            $subject instanceof Comment => $subject->loadMissing('user')->user,
            $subject instanceof Report => $this->resolveReportTargetUser($subject),
            default => null,
        };
    }

    private function resolveReportTargetUser(Report $report): ?User
    {
        $report->loadMissing('target');

        return match (true) {
            $report->target instanceof Post => $report->target->loadMissing('user')->user,
            $report->target instanceof Comment => $report->target->loadMissing('user')->user,
            default => null,
        };
    }

    private function assertSubjectBelongsToUser(User $user, Model $subject): void
    {
        $owner = $this->resolveTargetUser($subject);

        if ($owner === null || ! $owner->is($user)) {
            throw ValidationException::withMessages([
                'subject_id' => ['The selected subject does not belong to this user.'],
            ]);
        }
    }

    private function assertReportAffectsUser(User $user, Report $report): void
    {
        $owner = $this->resolveReportTargetUser($report);

        if ($owner === null || ! $owner->is($user)) {
            throw ValidationException::withMessages([
                'report_id' => ['The selected report does not target this user or this user\'s content.'],
            ]);
        }
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }
}
