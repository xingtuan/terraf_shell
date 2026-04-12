<?php

namespace App\Http\Resources;

use App\Models\MaterialSpec;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaterialSpec */
class MaterialSpecResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'key' => $this->key,
            'label' => $this->label,
            'value' => $this->value,
            'unit' => $this->unit,
            'detail' => $this->detail,
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
