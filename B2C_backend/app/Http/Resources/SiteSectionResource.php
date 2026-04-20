<?php

namespace App\Http\Resources;

use App\Models\SiteSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SiteSection */
class SiteSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page' => $this->page,
            'section' => $this->section,
            'locale' => $this->locale,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'body' => $this->body,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'image_url' => $this->image_url,
            'metadata' => $this->metadata,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
