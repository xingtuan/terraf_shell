<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;

class PostLikeController extends Controller
{
    public function store(Post $post, InteractionService $interactionService): JsonResponse
    {
        $post = $interactionService->likePost($post, request()->user());

        return $this->successResponse([
            'post_id' => $post->id,
            'likes_count' => (int) $post->likes_count,
            'is_liked' => true,
        ], 'Post liked successfully.');
    }

    public function destroy(Post $post, InteractionService $interactionService): JsonResponse
    {
        $post = $interactionService->unlikePost($post, request()->user());

        return $this->successResponse([
            'post_id' => $post->id,
            'likes_count' => (int) $post->likes_count,
            'is_liked' => false,
        ], 'Post like removed successfully.');
    }
}
