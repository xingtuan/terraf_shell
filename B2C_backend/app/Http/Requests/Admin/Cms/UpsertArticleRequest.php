<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpsertArticleRequest extends AdminCmsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [$this->requiredRule(), 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => [$this->requiredRule(), 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'media' => ['nullable', 'image', 'max:5120'],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }
}
