<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
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
            'reporter_id' => $this->reporter_id,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'target' => $this->targetSummary(),
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
            default => null,
        };
    }
}
