<?php

namespace App\Http\Requests\Content;

use App\Support\LocalizedContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPublishedMaterialsRequest extends FormRequest
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
            'featured' => ['nullable', 'boolean'],
            'locale' => ['nullable', Rule::in(LocalizedContent::supportedLocales())],
        ];
    }
}
