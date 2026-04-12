<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserNotification */
class UserNotificationResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title ?? data_get($this->data, 'title'),
            'body' => $this->body ?? data_get($this->data, 'body') ?? data_get($this->data, 'message'),
            'action_url' => $this->action_url ?? data_get($this->data, 'action_url'),
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'target' => $this->targetSummary(),
            'actor' => new UserResource($this->whenLoaded('actor')),
            'data' => $this->data ?? [],
            'is_read' => (bool) $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function targetSummary(): ?array
    {
        if (! $this->relationLoaded('target') || $this->target === null) {
            return null;
        }

        return match (true) {
            $this->target instanceof Post => [
                'id' => $this->target->id,
                'title' => $this->target->title,
                'slug' => $this->target->slug,
                'status' => $this->target->status,
            ],
            $this->target instanceof Comment => [
                'id' => $this->target->id,
                'post_id' => $this->target->post_id,
                'content' => $this->target->content,
                'status' => $this->target->status,
            ],
            $this->target instanceof User => [
                'id' => $this->target->id,
                'name' => $this->target->name,
                'username' => $this->target->username,
            ],
            default => null,
        };
    }
}
