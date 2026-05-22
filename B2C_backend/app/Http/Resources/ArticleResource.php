<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->localizedString($request, 'title'),
            'slug' => $this->slug,
            'excerpt' => $this->localizedString($request, 'excerpt'),
            'content' => $this->localizedString($request, 'content'),
            'category' => $this->localizedString($request, 'category'),
            'title_translations' => $this->localizedStringSet('title'),
            'excerpt_translations' => $this->localizedStringSet('excerpt'),
            'content_translations' => $this->localizedStringSet('content'),
            'category_translations' => $this->localizedStringSet('category'),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'media_url' => $this->media_url,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
