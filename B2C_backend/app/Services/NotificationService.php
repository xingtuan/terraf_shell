<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Jobs\CreateUserNotificationJob;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function dispatch(
        User $recipient,
        ?User $actor,
        NotificationType $type,
        Model $target,
        array $data = []
    ): void {
        if ($actor !== null && $actor->is($recipient)) {
            return;
        }

        CreateUserNotificationJob::dispatch(
            $recipient->id,
            $actor?->id,
            $type->value,
            $target->getMorphClass(),
            $target->getKey(),
            $data
        );
    }

    public function createRecord(
        int $recipientUserId,
        ?int $actorUserId,
        string $type,
        ?string $targetType,
        ?int $targetId,
        array $data = []
    ): UserNotification {
        return UserNotification::query()->create([
            'recipient_user_id' => $recipientUserId,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'data' => $data,
        ]);
    }

    public function listForUser(User $user, null|int|string $requestedPerPage = null): LengthAwarePaginator
    {
        return UserNotification::query()
            ->where('recipient_user_id', $user->id)
            ->with(['actor.profile', 'target'])
            ->orderByDesc('created_at')
            ->paginate($this->perPage($requestedPerPage))
            ->withQueryString();
    }

    public function markAsRead(UserNotification $notification): UserNotification
    {
        if (! $notification->is_read) {
            $notification->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        return $notification->fresh()->load(['actor.profile', 'target']);
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }
}
