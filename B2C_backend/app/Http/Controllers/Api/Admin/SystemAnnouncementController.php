<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSystemAnnouncementRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class SystemAnnouncementController extends Controller
{
    public function store(
        StoreSystemAnnouncementRequest $request,
        NotificationService $notificationService
    ): JsonResponse {
        $data = $request->validated();
        $sentCount = $notificationService->broadcastSystemAnnouncement(
            $request->user(),
            $data['title'],
            $data['body'],
            $data['action_url'] ?? null,
            $data['roles'] ?? []
        );

        return $this->successResponse([
            'title' => $data['title'],
            'body' => $data['body'],
            'action_url' => $data['action_url'] ?? null,
            'roles' => $data['roles'] ?? [],
            'sent_count' => $sentCount,
        ], 'System announcement sent successfully.', 201);
    }
}
