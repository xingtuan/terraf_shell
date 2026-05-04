<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSystemAnnouncementRequest;
use App\Services\GovernanceService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class SystemAnnouncementController extends Controller
{
    public function store(
        StoreSystemAnnouncementRequest $request,
        NotificationService $notificationService,
        GovernanceService $governanceService
    ): JsonResponse {
        $data = $request->validated();
        $sentCount = $notificationService->broadcastSystemAnnouncement(
            $request->user(),
            $data['title'],
            $data['body'],
            $data['action_url'] ?? null,
            $data['roles'] ?? [],
            (bool) ($data['send_email'] ?? false),
        );

        $governanceService->recordAdminAction(
            $request->user(),
            'notification.system_announcement_sent',
            $data['body'],
            [
                'title' => $data['title'],
                'roles' => $data['roles'] ?? [],
                'send_email' => (bool) ($data['send_email'] ?? false),
                'sent_count' => $sentCount,
                'action_url' => $data['action_url'] ?? null,
            ],
            $request->user(),
            $request->user()
        );

        return $this->successResponse([
            'title' => $data['title'],
            'body' => $data['body'],
            'action_url' => $data['action_url'] ?? null,
            'roles' => $data['roles'] ?? [],
            'send_email' => (bool) ($data['send_email'] ?? false),
            'sent_count' => $sentCount,
        ], 'System announcement sent successfully.', 201);
    }
}
