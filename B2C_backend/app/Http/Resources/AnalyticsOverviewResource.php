<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'generated_at' => data_get($this->resource, 'generated_at'),
            'summary' => data_get($this->resource, 'summary', []),
            'categories' => data_get($this->resource, 'categories', []),
            'activity' => data_get($this->resource, 'activity', []),
            'attention' => data_get($this->resource, 'attention', []),
            'funding' => data_get($this->resource, 'funding', []),
        ];
    }
}
