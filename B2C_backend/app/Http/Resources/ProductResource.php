<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'model' => $this->model,
            'finish' => $this->finish,
            'color' => $this->color,
            'technique' => $this->technique,
            'price_usd' => number_format((float) $this->price_usd, 2, '.', ''),
            'in_stock' => (bool) $this->in_stock,
            'image_url' => $this->image_url,
        ];
    }
}
