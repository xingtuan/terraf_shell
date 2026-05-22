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
            __('api.moderation.post_status_updated')
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
            __('api.moderation.post_featured_updated')
        );
    }

    public function rankingFormula(PostRankingService $postRankingService): JsonResponse
    {
        return $this->successResponse(
            $postRankingService->rankingFormula(),
            __('api.moderation.ranking_formula')
        );
    }
}
