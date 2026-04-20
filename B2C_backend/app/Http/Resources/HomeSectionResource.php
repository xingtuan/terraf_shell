<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\HomeSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HomeSection */
class HomeSectionResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->localizedString($request, 'title'),
            'subtitle' => $this->localizedString($request, 'subtitle'),
            'content' => $this->localizedString($request, 'content'),
            'cta_label' => $this->localizedString($request, 'cta_label'),
            'title_translations' => $this->localizedStringSet('title'),
            'subtitle_translations' => $this->localizedStringSet('subtitle'),
            'content_translations' => $this->localizedStringSet('content'),
            'cta_label_translations' => $this->localizedStringSet('cta_label'),
            'cta_url' => $this->cta_url,
            'payload' => $this->payload,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'media_url' => $this->media_url,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
