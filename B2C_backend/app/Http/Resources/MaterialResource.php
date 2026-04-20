<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
class MaterialResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->localizedString($request, 'title'),
            'slug' => $this->slug,
            'headline' => $this->localizedString($request, 'headline'),
            'summary' => $this->localizedString($request, 'summary'),
            'story_overview' => $this->localizedString($request, 'story_overview'),
            'science_overview' => $this->localizedString($request, 'science_overview'),
            'title_translations' => $this->localizedStringSet('title'),
            'headline_translations' => $this->localizedStringSet('headline'),
            'summary_translations' => $this->localizedStringSet('summary'),
            'story_overview_translations' => $this->localizedStringSet('story_overview'),
            'science_overview_translations' => $this->localizedStringSet('science_overview'),
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
