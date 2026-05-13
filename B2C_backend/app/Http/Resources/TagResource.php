<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tag */
class TagResource extends JsonResource
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
            'name_translations' => $this->localizedStringSet('name'),
            'posts_count' => $this->when(isset($this->posts_count), (int) $this->posts_count),
        ];
    }
}
