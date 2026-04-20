<?php

namespace App\Http\Resources;

use App\Models\Certification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Certification */
class CertificationResource extends JsonResource
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
            'description' => $this->description,
            'badge_color' => $this->badge_color,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
