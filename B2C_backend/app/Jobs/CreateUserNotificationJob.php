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
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?string $actionUrl = null,
        public readonly ?string $targetType = null,
        public readonly ?int $targetId = null,
        public readonly array $data = [],
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->createRecord(
            $this->recipientUserId,
            $this->actorUserId,
            $this->type,
            $this->title,
            $this->body,
            $this->actionUrl,
            $this->targetType,
            $this->targetId,
            $this->data
        );
    }
}
