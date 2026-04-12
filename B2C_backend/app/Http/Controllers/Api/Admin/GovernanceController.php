<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListGovernanceHistoryRequest;
use App\Http\Requests\Admin\ListUserViolationsRequest;
use App\Http\Requests\Admin\StoreUserViolationRequest;
use App\Http\Requests\Admin\UpdateUserViolationRequest;
use App\Http\Resources\AdminActionLogResource;
use App\Http\Resources\ModerationLogResource;
use App\Http\Resources\UserViolationResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserViolation;
use App\Services\GovernanceService;
use Illuminate\Http\JsonResponse;

class GovernanceController extends Controller
{
    public function userModerationHistory(
        ListGovernanceHistoryRequest $request,
        User $user,
        GovernanceService $governanceService
    ): JsonResponse {
        $logs = $governanceService->listModerationHistory($user, $request->validated());

        return $this->paginatedResponse(
            $logs,
            ModerationLogResource::collection($logs->getCollection())
        );
    }

    public function userAdminActions(
        ListGovernanceHistoryRequest $request,
        User $user,
        GovernanceService $governanceService
    ): JsonResponse {
        $logs = $governanceService->listAdminActionsForUser($user, $request->validated());

        return $this->paginatedResponse(
            $logs,
            AdminActionLogResource::collection($logs->getCollection())
        );
    }

    public function userViolations(
        ListUserViolationsRequest $request,
        User $user,
        GovernanceService $governanceService
    ): JsonResponse {
        $violations = $governanceService->listUserViolations($user, $request->validated());

        return $this->paginatedResponse(
            $violations,
            UserViolationResource::collection($violations->getCollection())
        );
    }

    public function storeUserViolation(
        StoreUserViolationRequest $request,
        User $user,
        GovernanceService $governanceService
    ): JsonResponse {
        $violation = $governanceService->storeManualViolation(
            $user,
            $request->user(),
            $request->validated()
        );

        return $this->successResponse(
            new UserViolationResource($violation),
            'User violation recorded successfully.',
            201
        );
    }

    public function updateUserViolation(
        UpdateUserViolationRequest $request,
        User $user,
        UserViolation $violation,
        GovernanceService $governanceService
    ): JsonResponse {
        abort_if($violation->user_id !== $user->id, 404);

        $violation = $governanceService->updateViolationStatus(
            $violation,
            $request->user(),
            $request->validated()['status'],
            $request->validated()['resolution_note'] ?? null
        );

        return $this->successResponse(
            new UserViolationResource($violation),
            'User violation updated successfully.'
        );
    }

    public function postReviewHistory(
        ListGovernanceHistoryRequest $request,
        Post $post,
        GovernanceService $governanceService
    ): JsonResponse {
        $logs = $governanceService->listReviewHistory($post, $request->validated());

        return $this->paginatedResponse(
            $logs,
            ModerationLogResource::collection($logs->getCollection())
        );
    }

    public function commentReviewHistory(
        ListGovernanceHistoryRequest $request,
        Comment $comment,
        GovernanceService $governanceService
    ): JsonResponse {
        $logs = $governanceService->listReviewHistory($comment, $request->validated());

        return $this->paginatedResponse(
            $logs,
            ModerationLogResource::collection($logs->getCollection())
        );
    }
}
