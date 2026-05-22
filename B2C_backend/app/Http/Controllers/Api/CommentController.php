<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\ReplyCommentRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Post $post, CommentService $commentService): JsonResponse
    {
        $comments = $commentService->listForPost($post, $request->user('sanctum'));

        return $this->successResponse(CommentResource::collection($comments));
    }

    public function store(StoreCommentRequest $request, Post $post, CommentService $commentService): JsonResponse
    {
        $comment = $commentService->create($post, $request->user(), $request->validated());

        return $this->successResponse(
            new CommentResource($comment),
            __('api.community.comment_added'),
            201
        );
    }

    public function reply(ReplyCommentRequest $request, Comment $comment, CommentService $commentService): JsonResponse
    {
        $reply = $commentService->reply($comment, $request->user(), $request->validated());

        return $this->successResponse(
            new CommentResource($reply),
            __('api.community.reply_added'),
            201
        );
    }

    public function update(UpdateCommentRequest $request, Comment $comment, CommentService $commentService): JsonResponse
    {
        $comment = $commentService->update($comment, $request->user(), $request->validated());

        return $this->successResponse(
            new CommentResource($comment),
            __('api.community.comment_updated')
        );
    }

    public function destroy(Comment $comment, CommentService $commentService): JsonResponse
    {
        $this->authorize('delete', $comment);
        $commentService->delete($comment);

        return $this->successResponse(null, __('api.community.comment_deleted'));
    }
}
