<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Tag $tag */
        $tag = $this->route('tag');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120', Rule::unique('tags', 'name')->ignore($tag)],
            'name_ko' => ['nullable', 'string', 'max:120'],
            'name_zh' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('tags', 'slug')->ignore($tag)],
        ];
    }
}
