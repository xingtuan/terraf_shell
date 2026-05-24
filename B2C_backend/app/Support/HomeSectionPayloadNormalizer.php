<?php

namespace App\Support;

class HomeSectionPayloadNormalizer
{
    private const LIST_KEYS = [
        'items',
        'cards',
        'metrics',
        'steps',
        'applications',
        'downloads',
        'links',
        'columns',
        'rows',
        'faqs',
        'social_links',
        'legal_links',
        'proofs',
        'features',
        'benefits',
        'topic_options',
        'legend',
    ];

    public static function normalize(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        return self::normalizeArray($payload);
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function normalizeArray(array $value, ?string $key = null): array
    {
        foreach ($value as $childKey => $childValue) {
            if (! is_array($childValue)) {
                continue;
            }

            $value[$childKey] = self::normalizeArray(
                $childValue,
                is_string($childKey) ? $childKey : null,
            );
        }

        if (
            $key !== null &&
            in_array($key, self::LIST_KEYS, true) &&
            ! array_is_list($value) &&
            self::hasOnlyListItemKeys($value)
        ) {
            return array_values($value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function hasOnlyListItemKeys(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        foreach (array_keys($value) as $key) {
            if (is_int($key)) {
                continue;
            }

            if (is_string($key) && ctype_digit($key)) {
                continue;
            }

            if (
                is_string($key) &&
                preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key)
            ) {
                continue;
            }

            return false;
        }

        return true;
    }
}
