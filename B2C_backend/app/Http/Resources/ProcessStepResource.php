<?php

namespace App\Http\Resources;

use App\Models\ProcessStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProcessStep */
class ProcessStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step_number' => $this->step_number,
            'locale' => $this->locale,
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
        ];
    }
}
