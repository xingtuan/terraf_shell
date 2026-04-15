<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $data = $this->all();

        if (filled($data['email'] ?? null) || filled($data['password'] ?? null)) {
            return;
        }

        $raw = trim((string) $this->getContent());

        if ($raw === '') {
            return;
        }

        $decodedJson = json_decode($raw, true);

        if (is_array($decodedJson)) {
            $this->merge($decodedJson);
            return;
        }

        // Some clients may send urlencoded body with an unexpected content type.
        parse_str($raw, $decodedForm);

        if (is_array($decodedForm) && $decodedForm !== []) {
            $this->merge($decodedForm);
        }
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