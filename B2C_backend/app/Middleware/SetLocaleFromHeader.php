<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    private const SUPPORTED = ['en', 'ko', 'zh'];

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $fallback = $this->normalizeLocale((string) config('app.locale', 'en')) ?? 'en';
        $explicit = $this->normalizeLocale((string) $request->header('X-Locale', ''));

        if ($explicit !== null) {
            return $explicit;
        }

        $header = (string) $request->header('Accept-Language', '');

        foreach (explode(',', $header) as $part) {
            $candidate = $this->normalizeLocale($part);

            if ($candidate !== null) {
                return $candidate;
            }
        }

        return $fallback;
    }

    private function normalizeLocale(string $value): ?string
    {
        $locale = strtolower(trim(explode(';', $value)[0] ?? ''));
        $locale = str_replace('_', '-', $locale);
        $locale = trim(explode('-', $locale)[0] ?? '');

        return in_array($locale, self::SUPPORTED, true) ? $locale : null;
    }
}
