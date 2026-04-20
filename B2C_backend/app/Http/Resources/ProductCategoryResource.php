<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductCategory */
class ProductCategoryResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->localizedString($request, 'name'),
            'description' => $this->localizedString($request, 'description'),
            'name_translations' => $this->localizedStringSet('name'),
            'description_translations' => $this->localizedStringSet('description'),
            'sort_order' => $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'products_count' => $this->when(isset($this->products_count), (int) $this->products_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
