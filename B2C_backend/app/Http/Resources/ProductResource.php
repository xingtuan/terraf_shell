<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
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
            'status' => $this->status,
            'featured' => (bool) $this->featured,
            'category_id' => $this->category_id,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'name' => $this->localizedString($request, 'name'),
            'short_description' => $this->localizedString($request, 'short_description'),
            'full_description' => $this->localizedString($request, 'full_description'),
            'features' => $this->localizedArray($request, 'features'),
            'availability_text' => $this->localizedString($request, 'availability_text'),
            'name_translations' => $this->localizedStringSet('name'),
            'short_description_translations' => $this->localizedStringSet('short_description'),
            'full_description_translations' => $this->localizedStringSet('full_description'),
            'features_translations' => $this->localizedArraySet('features'),
            'availability_text_translations' => $this->localizedStringSet('availability_text'),
            'cover_image_url' => $this->media_url,
            'gallery_images' => ProductImageResource::collection($this->whenLoaded('images')),
            'sort_order' => $this->sort_order,
            'price_from' => $this->price_from !== null ? (float) $this->price_from : null,
            'currency' => $this->currency,
            'inquiry_only' => (bool) $this->inquiry_only,
            'sample_request_enabled' => (bool) $this->sample_request_enabled,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
