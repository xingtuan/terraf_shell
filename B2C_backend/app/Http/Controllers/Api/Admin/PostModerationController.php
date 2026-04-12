<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePostFeatureRequest;
use App\Http\Requests\Admin\UpdatePostStatusRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\AdminModerationService;
use App\Services\PostRankingService;
use Illuminate\Http\JsonResponse;

class PostModerationController extends Controller
{
    public function updateStatus(
        UpdatePostStatusRequest $request,
        Post $post,
        AdminModerationService $moderationService
    ): JsonResponse {
        $post = $moderationService->updatePostStatus(
            $post,
            $request->validated()['status'],
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->successResponse(
            new PostResource($post),
            'Post status updated successfully.'
        );
    }

    public function updateFeaturedStatus(
        UpdatePostFeatureRequest $request,
        Post $post,
        AdminModerationService $moderationService
    ): JsonResponse {
        $post = $moderationService->updatePostFeaturedStatus(
            $post,
            (bool) $request->validated()['is_featured'],
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->successResponse(
            new PostResource($post),
            'Post featured status updated successfully.'
        );
    }

    public function rankingFormula(PostRankingService $postRankingService): JsonResponse
    {
        return $this->successResponse(
            $postRankingService->rankingFormula(),
            'Post ranking formula retrieved successfully.'
        );
    }
}
