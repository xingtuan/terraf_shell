<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpsertMaterialRequest extends AdminCmsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [$this->requiredRule(), 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'headline' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'story_overview' => ['nullable', 'string'],
            'science_overview' => ['nullable', 'string'],
            'certifications' => ['nullable', 'array'],
            'certifications.*.key' => ['nullable', 'string', 'max:120'],
            'certifications.*.label' => ['nullable', 'string', 'max:180'],
            'certifications.*.label_translations' => ['nullable', 'array'],
            'certifications.*.label_translations.*' => ['nullable', 'string', 'max:180'],
            'certifications.*.name' => ['nullable', 'string', 'max:180'],
            'certifications.*.name_translations' => ['nullable', 'array'],
            'certifications.*.name_translations.*' => ['nullable', 'string', 'max:180'],
            'certifications.*.value' => ['nullable', 'string', 'max:120'],
            'certifications.*.value_translations' => ['nullable', 'array'],
            'certifications.*.value_translations.*' => ['nullable', 'string', 'max:120'],
            'certifications.*.result' => ['nullable', 'string', 'max:120'],
            'certifications.*.result_translations' => ['nullable', 'array'],
            'certifications.*.result_translations.*' => ['nullable', 'string', 'max:120'],
            'certifications.*.unit' => ['nullable', 'string', 'max:40'],
            'certifications.*.status' => ['nullable', 'string', 'max:80'],
            'certifications.*.verified' => ['nullable', 'boolean'],
            'certifications.*.description' => ['nullable', 'string', 'max:1000'],
            'certifications.*.description_translations' => ['nullable', 'array'],
            'certifications.*.description_translations.*' => ['nullable', 'string', 'max:1000'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:180'],
            'certifications.*.issuer_translations' => ['nullable', 'array'],
            'certifications.*.issuer_translations.*' => ['nullable', 'string', 'max:180'],
            'certifications.*.tested_at' => ['nullable', 'date'],
            'certifications.*.document_url' => ['nullable', 'url', 'max:2048'],
            'technical_downloads' => ['nullable', 'array'],
            'technical_downloads.*.title' => ['nullable', 'string', 'max:180'],
            'technical_downloads.*.type' => ['nullable', 'string', 'max:80'],
            'technical_downloads.*.url' => ['nullable', 'url', 'max:2048'],
            'technical_downloads.*.status' => ['nullable', 'string', 'max:80'],
            'technical_downloads.*.description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'media' => ['nullable', 'image', 'max:5120'],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }
}
