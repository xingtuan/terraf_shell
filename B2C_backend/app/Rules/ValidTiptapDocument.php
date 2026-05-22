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
            $fail(__('api.community.invalid_tiptap_document', [
                'attribute' => __('validation.attributes.'.$attribute),
            ]));

            return;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'doc') {
            $fail(__('api.community.invalid_tiptap_document', [
                'attribute' => __('validation.attributes.'.$attribute),
            ]));
        }
    }
}
