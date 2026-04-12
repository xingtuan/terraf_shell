<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserViolationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserViolationRequest extends FormRequest
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
            'status' => ['required', Rule::in(UserViolationStatus::values())],
            'resolution_note' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn (): bool => $this->input('status') === UserViolationStatus::Resolved->value),
            ],
        ];
    }
}
