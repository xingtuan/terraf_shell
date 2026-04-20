<?php

namespace App\Http\Requests\Order;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_name' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:255'],
            'shipping_address_line1' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'shipping_state_province' => ['nullable', 'string', 'max:255'],
            'shipping_postal_code' => ['nullable', 'string', 'max:255'],
            'shipping_country' => ['required_without:address_id', 'nullable', 'string', 'size:2'],
            'customer_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
