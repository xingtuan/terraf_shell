<?php

namespace App\Http\Requests\Content;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPublishedProductsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['featured', 'newest', 'best_selling', 'price_low_to_high', 'price_high_to_low'])],
            'category' => [
                'nullable',
                'string',
                'max:120',
                Rule::exists(ProductCategory::class, 'slug')->where('is_active', true),
            ],
            'model' => ['nullable', Rule::in(array_keys(Product::MODEL_OPTIONS))],
            'finish' => ['nullable', Rule::in(array_keys(Product::FINISH_OPTIONS))],
            'color' => ['nullable', Rule::in(array_keys(Product::COLOR_OPTIONS))],
            'stock_status' => ['nullable', Rule::in(array_keys(Product::STOCK_STATUS_OPTIONS))],
            'use_case' => ['nullable', Rule::in(array_keys(Product::USE_CASE_OPTIONS))],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'gte:price_min'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
