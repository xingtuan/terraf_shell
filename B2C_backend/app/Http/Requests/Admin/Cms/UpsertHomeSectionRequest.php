<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpsertHomeSectionRequest extends AdminCmsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page_key' => ['nullable', 'string', Rule::in(['home', 'material'])],
            'key' => [$this->requiredRule(), 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:200'],
            'title_translations' => ['nullable', 'array'],
            'title_translations.*' => ['nullable', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'subtitle_translations' => ['nullable', 'array'],
            'subtitle_translations.*' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'content_translations' => ['nullable', 'array'],
            'content_translations.*' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_label_translations' => ['nullable', 'array'],
            'cta_label_translations.*' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:255'],
            'payload' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'media' => ['nullable', 'image', 'max:5120'],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }
}
