<?php

namespace App\Http\Requests\Admin;

use App\Enums\B2BInterestType;
use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListB2BLeadsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'lead_type' => ['nullable', Rule::in(B2BLeadType::values())],
            'interest_type' => ['nullable', Rule::in(B2BInterestType::values())],
            'application_type' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(B2BLeadStatus::values())],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [UserRole::Admin->value, UserRole::Moderator->value])
                ),
            ],
            'country' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'organization_type' => ['nullable', 'string', 'max:80'],
            'source_page' => ['nullable', 'string', 'max:120'],
            'created_from' => ['nullable', 'date'],
            'created_until' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('community.pagination.max_per_page')],
        ];
    }
}
