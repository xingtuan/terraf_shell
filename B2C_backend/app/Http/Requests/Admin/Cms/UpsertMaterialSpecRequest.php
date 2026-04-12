<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpsertMaterialSpecRequest extends AdminCmsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'material_id' => [$this->requiredRule(), 'integer', 'exists:materials,id'],
            'key' => ['nullable', 'string', 'max:100'],
            'label' => [$this->requiredRule(), 'string', 'max:150'],
            'value' => [$this->requiredRule(), 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:40'],
            'detail' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'media' => ['nullable', 'image', 'max:5120'],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }
}
