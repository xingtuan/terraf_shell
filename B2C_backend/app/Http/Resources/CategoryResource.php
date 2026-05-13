<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localizedString($request, 'name'),
            'slug' => $this->slug,
            'description' => $this->localizedString($request, 'description'),
            'name_translations' => $this->localizedStringSet('name'),
            'description_translations' => $this->localizedStringSet('description'),
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'posts_count' => $this->when(isset($this->posts_count), (int) $this->posts_count),
        ];
    }
}
