<?php

namespace App\Http\Resources;

use App\Models\B2BLead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin B2BLead */
class B2BLeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canSeeAdminFields = $request->user()?->canManageUsers() ?? false;

        return [
            'id' => $this->id,
            'reference' => $this->reference ?: sprintf('INQ-%06d', $this->id),
            'lead_type' => $this->lead_type,
            'inquiry_type' => $this->inquiry_type,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'organization_type' => $this->organization_type,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'region' => $this->region,
            'company_website' => $this->company_website,
            'job_title' => $this->job_title,
            'message' => $this->message,
            'source_page' => $this->source_page,
            'status' => $this->status,
            'partnership_inquiry' => new PartnershipInquiryResource($this->whenLoaded('partnershipInquiry')),
            'sample_request' => new SampleRequestResource($this->whenLoaded('sampleRequest')),
            'internal_notes' => $this->when($canSeeAdminFields, $this->internal_notes),
            'reviewed_by' => $this->when(
                $canSeeAdminFields && $this->relationLoaded('reviewer') && $this->reviewer !== null,
                fn (): UserResource => new UserResource($this->reviewer)
            ),
            'reviewed_at' => $this->when($canSeeAdminFields, $this->reviewed_at?->toISOString()),
            'metadata' => $this->when($canSeeAdminFields, $this->metadata ?? []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
