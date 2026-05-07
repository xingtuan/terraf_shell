<?php

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
        return [
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
