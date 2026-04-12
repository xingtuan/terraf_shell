<?php

namespace App\Http\Resources;

use App\Models\AdminActionLog;
use App\Support\AuditSubjectSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdminActionLog */
class AdminActionLogResource extends JsonResource
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
            'action' => $this->action,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject' => $this->subjectSummary(),
            'target_user_id' => $this->target_user_id,
            'target_user' => new UserResource($this->whenLoaded('targetUser')),
            'actor' => new UserResource($this->whenLoaded('actor')),
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function subjectSummary(): ?array
    {
        if (! $this->relationLoaded('subject')) {
            return null;
        }

        return AuditSubjectSummary::summarize($this->subject);
    }
}
