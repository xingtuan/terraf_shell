<?php

namespace App\Http\Resources;

use App\Models\IdeaMedia;
use App\Enums\IdeaMediaSourceType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IdeaMedia */
class IdeaMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_type' => $this->source_type,
            'media_type' => $this->media_type,
            'kind' => $this->kind,
            'title' => $this->title,
            'alt_text' => $this->alt_text,
            'original_name' => $this->original_name,
            'file_name' => $this->file_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'url' => $this->url,
            'preview_url' => $this->preview_url,
            'thumbnail_url' => $this->thumbnail_url,
            'external_url' => $this->external_url,
            'download_url' => $this->when(
                $this->sourceTypeValue() === IdeaMediaSourceType::Upload->value && filled($this->path),
                fn (): string => route(
                    'posts.attachments.download',
                    ['identifier' => $this->post_id, 'media' => $this->id],
                    false
                )
            ),
            'is_image' => $this->isImage(),
            'is_document' => $this->isDocument(),
            'is_external' => $this->isExternal(),
            'sort_order' => $this->sort_order,
            'download_count' => (int) ($this->download_count ?? 0),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
