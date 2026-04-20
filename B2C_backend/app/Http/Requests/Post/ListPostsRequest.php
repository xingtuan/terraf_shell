<?php

namespace App\Http\Requests\Post;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->boolean('mine') || $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'search' => ['nullable', 'string', 'min:2', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'liked_by' => ['nullable', 'string', 'max:30'],
            'creator' => ['nullable', 'string', 'max:120'],
            'creator_role' => ['nullable', Rule::in(UserRole::values())],
            'school_or_company' => ['nullable', 'string', 'max:160'],
            'region' => ['nullable', 'string', 'max:160'],
            'tag' => ['nullable', 'string', 'max:60'],
            'featured' => ['nullable', 'boolean'],
            'pinned' => ['nullable', 'boolean'],
            'mine' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['latest', 'hot', 'popular', 'trending', 'most_liked', 'most_commented', 'most_discussed'])],
            'status' => ['nullable', Rule::in(ContentStatus::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('community.pagination.max_per_page')],
        ];
    }
}
