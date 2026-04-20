<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\Post\ListPostsRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SearchPostsRequest extends ListPostsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['nullable', Rule::in(['posts'])],
        ]);
    }
}
