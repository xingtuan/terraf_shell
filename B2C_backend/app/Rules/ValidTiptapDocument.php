<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTiptapDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a valid Tiptap JSON document.');

            return;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'doc') {
            $fail('The :attribute must be a valid Tiptap JSON document.');
        }
    }
}
