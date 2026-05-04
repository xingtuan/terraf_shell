<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in([
                UserRole::Visitor->value,
                UserRole::Creator->value,
                UserRole::SmePartner->value,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Name is required.',
            'email.required'     => 'Email address is required.',
            'email.email'        => 'Please enter a valid email address.',
            'email.unique'       => 'This email is already registered. Try signing in instead.',
            'password.required'  => 'Password is required.',
            'password.confirmed' => "Passwords don't match.",
            'password.min'       => 'Password must be at least 8 characters.',
        ];
    }
}
