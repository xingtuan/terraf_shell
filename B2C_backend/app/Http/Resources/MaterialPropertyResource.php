<?php

namespace App\Http\Resources;

use App\Models\MaterialProperty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaterialProperty */
class MaterialPropertyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'locale' => $this->locale,
            'label' => $this->label,
            'value' => $this->value,
            'comparison' => $this->comparison,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
