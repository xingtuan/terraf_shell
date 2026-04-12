<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Post */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'is_pinned' => (bool) $this->is_pinned,
            'is_featured' => (bool) $this->is_featured,
            'engagement_score' => (int) ($this->engagement_score ?? 0),
            'trending_score' => (int) ($this->trending_score ?? 0),
            'comments_count' => (int) $this->comments_count,
            'likes_count' => (int) $this->likes_count,
            'favorites_count' => (int) $this->favorites_count,
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_favorited' => (bool) ($this->is_favorited ?? false),
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'images' => PostImageResource::collection($this->whenLoaded('images')),
            'media' => IdeaMediaResource::collection($this->whenLoaded('media')),
            'featured_by' => $this->when(
                $request->user()?->canModerate() ?? false,
                $this->featured_by
            ),
            'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'featured_at' => $this->featured_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
