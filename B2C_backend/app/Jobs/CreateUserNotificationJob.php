<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateUserNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $recipientUserId,
        public readonly ?int $actorUserId,
        public readonly string $type,
        public readonly ?string $targetType,
        public readonly ?int $targetId,
        public readonly array $data = [],
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->createRecord(
            $this->recipientUserId,
            $this->actorUserId,
            $this->type,
            $this->targetType,
            $this->targetId,
            $this->data
        );
    }
}
