<?php

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
class MaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'headline' => $this->headline,
            'summary' => $this->summary,
            'story_overview' => $this->story_overview,
            'science_overview' => $this->science_overview,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => $this->sort_order,
            'media_url' => $this->media_url,
            'specs_count' => $this->when(isset($this->specs_count), (int) $this->specs_count),
            'story_sections_count' => $this->when(isset($this->story_sections_count), (int) $this->story_sections_count),
            'applications_count' => $this->when(isset($this->applications_count), (int) $this->applications_count),
            'specs' => MaterialSpecResource::collection($this->whenLoaded('specs')),
            'story_sections' => MaterialStorySectionResource::collection($this->whenLoaded('storySections')),
            'applications' => MaterialApplicationResource::collection($this->whenLoaded('applications')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
