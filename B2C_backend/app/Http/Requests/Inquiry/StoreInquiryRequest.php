<?php

namespace App\Http\Requests\Inquiry;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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

    public function messages(): array
    {
        return [
            'name.required'         => 'Name is required.',
            'company_name.required' => 'Company name is required.',
            'email.required'        => 'Email address is required.',
            'email.email'           => 'Please enter a valid email address.',
            'inquiry_type.required' => 'Application is required.',
            'message.required'      => 'Project details are required.',
            'company_website.url'   => 'Please enter a valid website URL (including https://).',
        ];
    }
}
