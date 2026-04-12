<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListContentRequest extends AdminCmsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
