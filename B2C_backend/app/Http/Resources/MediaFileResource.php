<?php

namespace App\Http\Resources;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MediaFile */
class MediaFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'path' => $this->path,
            'type' => $this->type,
            'mime' => $this->mime_type,
            'size' => $this->size,
            'original_name' => $this->original_name,
        ];
    }
}
