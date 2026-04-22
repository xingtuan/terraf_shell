<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum') ?? $request->user();

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'body' => $this->content,
            'content' => $this->content,
            'status' => $this->status,
            'likes_count' => (int) $this->likes_count,
            'is_liked' => (bool) ($this->is_liked ?? false),
            'user' => new UserResource($this->whenLoaded('user')),
            'post' => $this->whenLoaded('post', fn (): array => [
                'id' => $this->post->id,
                'title' => $this->post->title,
                'slug' => $this->post->slug,
            ]),
            'replies_count' => $this->relationLoaded('replies')
                ? $this->replies->count()
                : (int) ($this->replies_count ?? 0),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'can_edit' => $viewer?->can('update', $this->resource) ?? false,
            'can_delete' => $viewer?->can('delete', $this->resource) ?? false,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
