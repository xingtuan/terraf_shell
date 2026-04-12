<?php

namespace App\Http\Requests\Admin;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
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
            'status' => ['nullable', Rule::in(B2BLeadStatus::values())],
            'country' => ['nullable', 'string', 'max:120'],
            'organization_type' => ['nullable', 'string', 'max:80'],
            'source_page' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('community.pagination.max_per_page')],
        ];
    }
}
