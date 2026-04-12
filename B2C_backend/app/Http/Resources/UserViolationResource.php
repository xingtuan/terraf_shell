<?php

namespace App\Http\Resources;

use App\Models\UserViolation;
use App\Support\AuditSubjectSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserViolation */
class UserViolationResource extends JsonResource
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
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'reason' => $this->reason,
            'resolution_note' => $this->resolution_note,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject' => $this->subjectSummary(),
            'report_id' => $this->report_id,
            'report' => $this->reportSummary(),
            'user' => new UserResource($this->whenLoaded('user')),
            'actor' => new UserResource($this->whenLoaded('actor')),
            'resolver' => new UserResource($this->whenLoaded('resolver')),
            'metadata' => $this->metadata ?? [],
            'occurred_at' => $this->occurred_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
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

    private function reportSummary(): ?array
    {
        if (! $this->relationLoaded('report')) {
            return null;
        }

        return AuditSubjectSummary::summarize($this->report);
    }
}
