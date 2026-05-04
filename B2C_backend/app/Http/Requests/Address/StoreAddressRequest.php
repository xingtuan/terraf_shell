<?php

namespace App\Http\Requests\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required' => 'Recipient name is required.',
            'address_line1.required'  => 'Address is required.',
            'city.required'           => 'City is required.',
            'country.required'        => 'Country is required.',
            'country.size'            => 'Country must be a 2-letter ISO code.',
        ];
    }
}
