<?php

namespace App\Services;

use Illuminate\Support\Str;

class SensitiveContentService
{
    public function enabled(): bool
    {
        return (bool) config('community.moderation.sensitive_words.enabled', false);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array{matched_terms: array<int, string>, matched_fields: array<string, array<int, string>>}
     */
    public function scan(array $fields): array
    {
        if (! $this->enabled()) {
            return [
                'matched_terms' => [],
                'matched_fields' => [],
            ];
        }

        $configuredTerms = collect(config('community.moderation.sensitive_words.terms', []))
            ->map(fn ($term): string => trim(Str::lower((string) $term)))
            ->filter()
            ->unique()
            ->values();

        if ($configuredTerms->isEmpty()) {
            return [
                'matched_terms' => [],
                'matched_fields' => [],
            ];
        }

        $matchedTerms = [];
        $matchedFields = [];

        foreach ($fields as $field => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $text = trim(Str::lower((string) $value));

            if ($text === '') {
                continue;
            }

            $fieldMatches = $configuredTerms
                ->filter(fn (string $term): bool => str_contains($text, $term))
                ->values()
                ->all();

            if ($fieldMatches === []) {
                continue;
            }

            $matchedFields[(string) $field] = $fieldMatches;
            $matchedTerms = array_merge($matchedTerms, $fieldMatches);
        }

        return [
            'matched_terms' => array_values(array_unique($matchedTerms)),
            'matched_fields' => $matchedFields,
        ];
    }
}
