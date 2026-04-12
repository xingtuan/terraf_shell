<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePostStatusRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\AdminModerationService;
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
}
