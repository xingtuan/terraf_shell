<?php

namespace App\Http\Requests\Order;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isGuest = $this->user('sanctum') === null;

        return [
            'guest_email' => [Rule::requiredIf($isGuest), 'nullable', 'email:rfc', 'max:255'],
            'address_id' => [
                'nullable',
                'integer',
                'exists:addresses,id',
                Rule::prohibitedIf($isGuest),
            ],
            'shipping_method_code' => ['required', 'string', 'max:80'],
            'shipping_name' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_phone' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_address_line1' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_state_province' => ['nullable', 'string', 'max:255'],
            'shipping_postal_code' => ['required_without:address_id', 'nullable', 'string', 'max:20'],
            'shipping_country' => ['required_without:address_id', 'nullable', 'string', 'size:2', 'in:NZ'],
            'shipping_is_rural' => ['nullable', 'boolean'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('shipping_country')) {
            $this->merge([
                'shipping_country' => strtoupper((string) $this->input('shipping_country')),
            ]);
        }
    }
}
