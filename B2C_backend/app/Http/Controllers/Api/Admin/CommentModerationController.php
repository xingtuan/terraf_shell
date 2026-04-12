<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCommentStatusRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Services\AdminModerationService;
use Illuminate\Http\JsonResponse;

class CommentModerationController extends Controller
{
    public function updateStatus(
        UpdateCommentStatusRequest $request,
        Comment $comment,
        AdminModerationService $moderationService
    ): JsonResponse {
        $comment = $moderationService->updateCommentStatus(
            $comment,
            $request->validated()['status'],
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return $this->successResponse(
            new CommentResource($comment),
            'Comment status updated successfully.'
        );
    }
}
