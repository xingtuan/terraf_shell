<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function store(Post $post, InteractionService $interactionService): JsonResponse
    {
        $post = $interactionService->favoritePost($post, request()->user());

        return $this->successResponse([
            'post_id' => $post->id,
            'favorites_count' => (int) $post->favorites_count,
            'is_favorited' => true,
        ], 'Post added to favorites.');
    }

    public function destroy(Post $post, InteractionService $interactionService): JsonResponse
    {
        $post = $interactionService->unfavoritePost($post, request()->user());

        return $this->successResponse([
            'post_id' => $post->id,
            'favorites_count' => (int) $post->favorites_count,
            'is_favorited' => false,
        ], 'Post removed from favorites.');
    }
}
