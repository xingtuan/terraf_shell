<?php

namespace App\Http\Requests\Inquiry;

use App\Enums\B2BInterestType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:150'],
            'organization_type' => ['nullable', 'string', 'max:80'],
            'interest_type' => ['nullable', Rule::in(B2BInterestType::values())],
            'application_type' => ['nullable', 'string', 'max:150'],
            'expected_use_case' => ['nullable', 'string', 'max:1000'],
            'estimated_quantity' => ['nullable', 'string', 'max:120'],
            'timeline' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'company_website' => ['nullable', 'url', 'max:2048'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'inquiry_type' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'source_page' => ['nullable', 'string', 'max:120'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
