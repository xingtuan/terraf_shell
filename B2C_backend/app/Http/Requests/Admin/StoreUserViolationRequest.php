<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserViolationRequest extends FormRequest
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
            'type' => ['required', Rule::in(UserViolationType::values())],
            'severity' => ['required', Rule::in(UserViolationSeverity::values())],
            'reason' => ['required', 'string', 'max:1000'],
            'subject_type' => ['nullable', Rule::in(['post', 'comment', 'user'])],
            'subject_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn (): bool => $this->filled('subject_type')),
            ],
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
