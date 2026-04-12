<?php

namespace App\Http\Requests\Admin;

use App\Enums\B2BLeadStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['nullable', Rule::in(B2BLeadStatus::values()), 'required_without:internal_notes'],
            'internal_notes' => ['nullable', 'string', 'max:5000', 'required_without:status'],
        ];
    }
}
