<?php

namespace App\Http\Resources;

use App\Models\MaterialApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaterialApplication */
class MaterialApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'audience' => $this->audience,
            'cta_label' => $this->cta_label,
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
