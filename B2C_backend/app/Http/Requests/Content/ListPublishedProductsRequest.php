<?php

namespace App\Http\Requests\Content;

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
            'category' => ['nullable', Rule::in(['tableware', 'planters', 'wellness_interior', 'architectural'])],
            'model' => ['nullable', Rule::in(['lite_15', 'heritage_16'])],
            'color' => ['nullable', Rule::in(['ocean_bone', 'forged_ash'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
