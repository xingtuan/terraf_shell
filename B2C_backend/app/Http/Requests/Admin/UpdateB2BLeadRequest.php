<?php

namespace App\Http\Requests\Admin;

use App\Enums\B2BLeadStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateB2BLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(B2BLeadStatus::values())],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [UserRole::Admin->value, UserRole::Moderator->value])
                ),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasAny(['status', 'internal_notes', 'assigned_to'])) {
                $validator->errors()->add('status', 'Provide at least one field to update.');
            }
        });
    }
}
