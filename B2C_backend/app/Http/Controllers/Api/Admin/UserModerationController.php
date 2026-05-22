<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\UpdateUserAccountStatusRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminModerationService;
use Illuminate\Http\JsonResponse;

class UserModerationController extends Controller
{
    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user,
        AdminModerationService $moderationService
    ): JsonResponse {
        $user = $moderationService->updateUserRole(
            $user,
            $request->validated()['role'],
            $request->user(),
        );

        return $this->successResponse(
            new UserResource($user),
            __('api.moderation.user_role_updated')
        );
    }

    public function updateAccountStatus(
        UpdateUserAccountStatusRequest $request,
        User $user,
        AdminModerationService $moderationService
    ): JsonResponse {
        $user = $moderationService->updateAccountStatus(
            $user,
            $request->validated()['account_status'],
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->successResponse(
            new UserResource($user),
            __('api.moderation.user_account_status_updated')
        );
    }

    public function ban(
        BanUserRequest $request,
        User $user,
        AdminModerationService $moderationService
    ): JsonResponse {
        $user = $moderationService->banUser(
            $user,
            (bool) $request->validated()['is_banned'],
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->successResponse(
            new UserResource($user),
            $user->isBanned() ? __('api.moderation.user_banned') : __('api.moderation.user_unbanned')
        );
    }
}
