<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tag */
class TagResource extends JsonResource
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
            'name' => $this->name,
            'name_ko' => $this->name_ko,
            'name_zh' => $this->name_zh,
            'slug' => $this->slug,
            'posts_count' => $this->when(isset($this->posts_count), (int) $this->posts_count),
        ];
    }
}
