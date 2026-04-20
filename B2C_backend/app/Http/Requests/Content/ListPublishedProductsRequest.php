<?php

namespace App\Http\Requests\Content;

use App\Support\LocalizedContent;
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
            'category' => ['nullable', 'string', 'max:120'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['sort_order', 'newest'])],
            'locale' => ['nullable', Rule::in(LocalizedContent::supportedLocales())],
        ];
    }
}
