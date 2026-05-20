<?php

namespace App\Http\Requests\Admin\Cms;

use App\Enums\PublishStatus;
use App\Models\HomeSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpsertHomeSectionRequest extends AdminCmsRequest
{
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('page_key')) {
            $data['page_key'] = strtolower(trim((string) $this->input('page_key')));
        }

        if ($this->has('key')) {
            $key = Str::slug((string) $this->input('key'), '_');
            $data['key'] = $key === '' ? $this->input('key') : $key;
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page_key' => ['nullable', 'string', Rule::in(HomeSection::allowedPageKeys())],
            'key' => [$this->requiredRule(), 'string', 'max:120', $this->scopedKeyUniqueRule()],
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
            'cta_url' => ['nullable', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(PublishStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'media_url' => ['nullable', 'string', 'max:255'],
            'media' => ['nullable', 'image', 'max:5120'],
            'remove_media' => ['nullable', 'boolean'],
        ];
    }

    private function scopedKeyUniqueRule(): Unique
    {
        $record = $this->route('homeSection');
        $pageKey = $this->input('page_key');

        if (! is_string($pageKey) || trim($pageKey) === '') {
            $pageKey = $record instanceof HomeSection ? $record->page_key : 'home';
        }

        $rule = Rule::unique('home_sections', 'key')
            ->where(fn (Builder $query): Builder => $query->where('page_key', $pageKey));

        return $record instanceof HomeSection
            ? $rule->ignore($record->getKey())
            : $rule;
    }
}
