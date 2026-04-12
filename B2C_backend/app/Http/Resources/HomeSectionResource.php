<?php

namespace App\Http\Resources;

use App\Models\HomeSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HomeSection */
class HomeSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'cta_label' => $this->cta_label,
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
