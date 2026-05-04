<?php

namespace App\Jobs\Email;

use App\Models\EmailLog;
use App\Services\Email\EmailDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $emailLogId,
    ) {}

    public function handle(EmailDispatchService $emailDispatchService): void
    {
        $log = EmailLog::query()->find($this->emailLogId);

        if (! $log instanceof EmailLog || $log->status === EmailLog::STATUS_SENT) {
            return;
        }

        $emailDispatchService->sendLog($log);
    }
}
