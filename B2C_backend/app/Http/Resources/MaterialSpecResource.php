<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\MaterialSpec;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaterialSpec */
class MaterialSpecResource extends JsonResource
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
            'key' => $this->key,
            'label' => $this->localizedString($request, 'label'),
            'value' => $this->localizedString($request, 'value'),
            'unit' => $this->unit,
            'detail' => $this->localizedString($request, 'detail'),
            'label_translations' => $this->localizedStringSet('label'),
            'value_translations' => $this->localizedStringSet('value'),
            'detail_translations' => $this->localizedStringSet('detail'),
            'icon' => $this->icon,
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
