<?php

namespace App\Support;

class FrontendUrl
{
    /**
     * @param  array<string, mixed>  $query
     */
    public static function to(string $path, array $query = []): ?string
    {
        $baseUrl = rtrim((string) config('services.frontend.url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        $url = $baseUrl.'/'.ltrim($path, '/');
        $query = array_filter($query, fn (mixed $value): bool => filled($value));

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }

    public static function emailVerificationUrl(string $status, ?string $locale = null): ?string
    {
        return self::to(
            (string) config('services.frontend.email_verification_path', '/email-verified'),
            [
                'status' => $status,
                'locale' => $locale,
            ],
        );
    }

    public static function currentLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['en', 'ko', 'zh'], true) ? $locale : 'en';
    }
}
