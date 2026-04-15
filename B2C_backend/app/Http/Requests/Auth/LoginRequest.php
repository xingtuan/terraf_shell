<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Fallback for clients/proxies that send JSON without a proper content type.
        if ($this->filled('email') || $this->filled('password')) {
            return;
        }

        $raw = trim((string) $this->getContent());

        if ($raw === '') {
            return;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return;
        }

        $this->merge([
            'email' => $decoded['email'] ?? $this->input('email'),
            'password' => $decoded['password'] ?? $this->input('password'),
            'device_name' => $decoded['device_name'] ?? $this->input('device_name'),
        ]);
    }

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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}