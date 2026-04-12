<?php

namespace App\Http\Resources;

use App\Models\SampleRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SampleRequest */
class SampleRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'material_interest' => $this->material_interest,
            'quantity_estimate' => $this->quantity_estimate,
            'shipping_country' => $this->shipping_country,
            'shipping_region' => $this->shipping_region,
            'shipping_address' => $this->shipping_address,
            'intended_use' => $this->intended_use,
            'metadata' => $this->metadata ?? [],
        ];
    }
}
