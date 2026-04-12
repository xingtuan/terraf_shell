<?php

namespace App\Http\Requests\Lead;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreSampleRequestRequest extends BaseLeadRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'material_interest' => ['required', 'string', 'max:150'],
            'quantity_estimate' => ['nullable', 'string', 'max:120'],
            'shipping_country' => ['nullable', 'string', 'max:120'],
            'shipping_region' => ['nullable', 'string', 'max:120'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'intended_use' => ['required', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);
    }
}
