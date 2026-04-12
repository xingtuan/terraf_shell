<?php

namespace App\Http\Resources;

use App\Models\PartnershipInquiry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PartnershipInquiry */
class PartnershipInquiryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'collaboration_type' => $this->collaboration_type,
            'collaboration_goal' => $this->collaboration_goal,
            'project_stage' => $this->project_stage,
            'timeline' => $this->timeline,
            'metadata' => $this->metadata ?? [],
        ];
    }
}
