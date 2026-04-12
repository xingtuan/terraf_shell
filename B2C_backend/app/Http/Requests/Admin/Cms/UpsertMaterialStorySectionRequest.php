<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpsertMaterialStorySectionRequest extends AdminCmsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'material_id' => [$this->requiredRule(), 'integer', 'exists:materials,id'],
            'title' => [$this->requiredRule(), 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => [$this->requiredRule(), 'string'],
            'highlight' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'media' => ['nullable', 'image', 'max:5120'],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }
}
