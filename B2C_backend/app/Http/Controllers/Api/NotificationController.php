<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\ListNotificationsRequest;
use App\Http\Requests\Notification\MarkNotificationReadRequest;
use App\Http\Resources\UserNotificationResource;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Support\PaginatesResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(ListNotificationsRequest $request, NotificationService $notificationService): JsonResponse
    {
        $filters = $request->validated();
        $notifications = $notificationService->listForUser(
            $request->user(),
            $filters,
            $filters['per_page'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => UserNotificationResource::collection($notifications->getCollection())->resolve($request),
            'meta' => array_merge(
                PaginatesResources::meta($notifications),
                ['unread_count' => $notificationService->unreadCount($request->user())]
            ),
        ]);
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

    public function markAllRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $markedCount = $notificationService->markAllAsRead($request->user());

        return $this->successResponse([
            'marked_count' => $markedCount,
        ], 'Notifications marked as read.');
    }
}
