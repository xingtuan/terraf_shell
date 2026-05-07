<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExternalSafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $scheme = strtolower((string) parse_url((string) $value, PHP_URL_SCHEME));
        $host = parse_url((string) $value, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || blank($host)) {
            $fail(__('validation.url', ['attribute' => $attribute]));
        }
    }
}
