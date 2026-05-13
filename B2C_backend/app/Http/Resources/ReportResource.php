<?php

namespace App\Http\Resources;

use App\Enums\ReportResolutionAction;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
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
        $user = $request->user();
        $isStaff = $user?->canModerate() ?? false;

        $data = [
            'id' => $this->id,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'target' => $this->targetSummary(),
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'public_note' => $this->public_note,
            'resolution_action' => $isStaff ? $this->resolution_action : $this->publicResolutionAction(),
            'resolution_action_label' => $this->publicResolutionActionLabel(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'dismissed_at' => $this->dismissed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        if ($isStaff) {
            $data += [
                'reporter_id' => $this->reporter_id,
                'moderator_note' => $this->moderator_note,
                'reviewed_by' => $this->reviewed_by,
                'reporter' => new UserResource($this->whenLoaded('reporter')),
                'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            ];
        }

        return $data;
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

    private function publicResolutionAction(): ?string
    {
        $action = ReportResolutionAction::tryFrom((string) $this->resolution_action);

        if ($action === null || $action === ReportResolutionAction::None) {
            return $action?->value;
        }

        return 'action_taken';
    }

    private function publicResolutionActionLabel(): ?string
    {
        $action = ReportResolutionAction::tryFrom((string) $this->resolution_action);

        if ($action === null) {
            return null;
        }

        return $action->publicLabel();
    }
}
