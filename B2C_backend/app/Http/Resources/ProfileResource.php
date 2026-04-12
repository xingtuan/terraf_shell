<?php

namespace App\Http\Resources;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Profile */
class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bio' => $this->bio,
            'school_or_company' => $this->school_or_company,
            'region' => $this->region,
            'location' => $this->location,
            'portfolio_url' => $this->portfolio_url,
            'website' => $this->website,
            'open_to_collab' => (bool) $this->open_to_collab,
            'avatar_url' => $this->avatar_url,
        ];
    }
}
