<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\MaterialApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaterialApplication */
class MaterialApplicationResource extends JsonResource
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
            'description' => $this->localizedString($request, 'description'),
            'audience' => $this->localizedString($request, 'audience'),
            'cta_label' => $this->localizedString($request, 'cta_label'),
            'title_translations' => $this->localizedStringSet('title'),
            'subtitle_translations' => $this->localizedStringSet('subtitle'),
            'description_translations' => $this->localizedStringSet('description'),
            'audience_translations' => $this->localizedStringSet('audience'),
            'cta_label_translations' => $this->localizedStringSet('cta_label'),
            'cta_url' => $this->cta_url,
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
