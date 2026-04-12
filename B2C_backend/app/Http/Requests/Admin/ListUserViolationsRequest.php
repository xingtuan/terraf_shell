<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUserViolationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canModerate() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(UserViolationType::values())],
            'severity' => ['nullable', Rule::in(UserViolationSeverity::values())],
            'status' => ['nullable', Rule::in(UserViolationStatus::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('community.pagination.max_per_page')],
        ];
    }
}
