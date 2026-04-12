<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;

class CommentLikeController extends Controller
{
    public function store(Comment $comment, InteractionService $interactionService): JsonResponse
    {
        $comment = $interactionService->likeComment($comment, request()->user());

        return $this->successResponse([
            'comment_id' => $comment->id,
            'likes_count' => (int) $comment->likes_count,
            'is_liked' => true,
        ], 'Comment liked successfully.');
    }

    public function destroy(Comment $comment, InteractionService $interactionService): JsonResponse
    {
        $comment = $interactionService->unlikeComment($comment, request()->user());

        return $this->successResponse([
            'comment_id' => $comment->id,
            'likes_count' => (int) $comment->likes_count,
            'is_liked' => false,
        ], 'Comment like removed successfully.');
    }
}
