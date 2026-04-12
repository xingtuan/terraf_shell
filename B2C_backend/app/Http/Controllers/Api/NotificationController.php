<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\MarkNotificationReadRequest;
use App\Http\Resources\UserNotificationResource;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService): JsonResponse
    {
        $notifications = $notificationService->listForUser(
            $request->user(),
            $request->query('per_page')
        );

        return $this->paginatedResponse(
            $notifications,
            UserNotificationResource::collection($notifications->getCollection())
        );
    }

    public function markRead(
        MarkNotificationReadRequest $request,
        UserNotification $notification,
        NotificationService $notificationService
    ): JsonResponse {
        $notification = $notificationService->markAsRead($notification);

        return $this->successResponse(
            new UserNotificationResource($notification),
            'Notification marked as read.'
        );
    }
}
