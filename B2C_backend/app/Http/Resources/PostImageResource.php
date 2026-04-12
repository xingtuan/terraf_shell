<?php

namespace App\Http\Resources;

use App\Models\IdeaMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IdeaMedia */
class PostImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'preview_url' => $this->preview_url,
            'thumbnail_url' => $this->thumbnail_url,
            'alt_text' => $this->alt_text,
            'kind' => $this->kind,
            'sort_order' => $this->sort_order,
        ];
    }
}
