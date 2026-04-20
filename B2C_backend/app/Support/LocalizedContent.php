<?php

namespace App\Support;

class LocalizedContent
{
    public const DEFAULT_LOCALE = 'en';

    /**
     * @var array<int, string>
     */
    public const SUPPORTED_LOCALES = ['en', 'ko', 'zh'];

    /**
     * @return array<int, string>
     */
    public static function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function resolveLocale(?string $requestedLocale = null): string
    {
        $normalizedLocale = strtolower(trim((string) $requestedLocale));

        if (in_array($normalizedLocale, self::SUPPORTED_LOCALES, true)) {
            return $normalizedLocale;
        }

        $appLocale = strtolower((string) config('app.locale', self::DEFAULT_LOCALE));

        return in_array($appLocale, self::SUPPORTED_LOCALES, true)
            ? $appLocale
            : self::DEFAULT_LOCALE;
    }

    /**
     * @return array<string, string>
     */
    public static function normalizeStringTranslations(mixed $translations, ?string $fallback = null): array
    {
        $normalized = [];

        if (is_array($translations)) {
            foreach (self::SUPPORTED_LOCALES as $locale) {
                $value = $translations[$locale] ?? null;

                if (! is_string($value)) {
                    continue;
                }

                $trimmed = trim($value);

                if ($trimmed !== '') {
                    $normalized[$locale] = $trimmed;
                }
            }
        }

        $fallbackValue = is_string($fallback) ? trim($fallback) : '';

        if ($normalized === [] && $fallbackValue !== '') {
            $normalized[self::DEFAULT_LOCALE] = $fallbackValue;
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>|null  $fallback
     * @return array<string, array<int, string>>
     */
    public static function normalizeArrayTranslations(mixed $translations, ?array $fallback = null): array
    {
        $normalized = [];

        if (is_array($translations)) {
            foreach (self::SUPPORTED_LOCALES as $locale) {
                $value = $translations[$locale] ?? null;

                if (! is_array($value)) {
                    continue;
                }

                $items = array_values(array_filter(
                    array_map(
                        static fn (mixed $item): ?string => is_string($item) && trim($item) !== ''
                            ? trim($item)
                            : null,
                        $value
                    )
                ));

                if ($items !== []) {
                    $normalized[$locale] = $items;
                }
            }
        }

        $fallbackItems = array_values(array_filter(
            array_map(
                static fn (mixed $item): ?string => is_string($item) && trim($item) !== ''
                    ? trim($item)
                    : null,
                $fallback ?? []
            )
        ));

        if ($normalized === [] && $fallbackItems !== []) {
            $normalized[self::DEFAULT_LOCALE] = $fallbackItems;
        }

        return $normalized;
    }

    public static function resolveString(
        mixed $translations,
        ?string $requestedLocale = null,
        ?string $fallback = null,
    ): ?string {
        $normalized = self::normalizeStringTranslations($translations, $fallback);
        $locale = self::resolveLocale($requestedLocale);
        $firstValue = reset($normalized);

        return $normalized[$locale]
            ?? $normalized[self::DEFAULT_LOCALE]
            ?? (is_string($firstValue) ? $firstValue : null)
            ?? self::normalizeStringTranslations([], $fallback)[self::DEFAULT_LOCALE]
            ?? null;
    }

    /**
     * @param  array<int, string>|null  $fallback
     * @return array<int, string>
     */
    public static function resolveArray(
        mixed $translations,
        ?string $requestedLocale = null,
        ?array $fallback = null,
    ): array {
        $normalized = self::normalizeArrayTranslations($translations, $fallback);
        $locale = self::resolveLocale($requestedLocale);
        $firstValue = reset($normalized);

        return $normalized[$locale]
            ?? $normalized[self::DEFAULT_LOCALE]
            ?? (is_array($firstValue) ? $firstValue : [])
            ?? array_values($fallback ?? []);
    }
}
