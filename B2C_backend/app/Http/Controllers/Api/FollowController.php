<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    public function store(User $user, InteractionService $interactionService): JsonResponse
    {
        $this->authorize('follow', $user);
        $interactionService->follow($user, request()->user());

        return $this->successResponse([
            'user_id' => $user->id,
            'is_following' => true,
        ], __('api.community.user_followed'));
    }

    public function destroy(User $user, InteractionService $interactionService): JsonResponse
    {
        $this->authorize('follow', $user);
        $interactionService->unfollow($user, request()->user());

        return $this->successResponse([
            'user_id' => $user->id,
            'is_following' => false,
        ], __('api.community.user_unfollowed'));
    }
}
