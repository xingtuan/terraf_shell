<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\MaterialStorySection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaterialStorySection */
class MaterialStorySectionResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'title' => $this->localizedString($request, 'title'),
            'subtitle' => $this->localizedString($request, 'subtitle'),
            'content' => $this->localizedString($request, 'content'),
            'highlight' => $this->localizedString($request, 'highlight'),
            'title_translations' => $this->localizedStringSet('title'),
            'subtitle_translations' => $this->localizedStringSet('subtitle'),
            'content_translations' => $this->localizedStringSet('content'),
            'highlight_translations' => $this->localizedStringSet('highlight'),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'media_url' => $this->media_url,
            'material' => new MaterialResource($this->whenLoaded('material')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
