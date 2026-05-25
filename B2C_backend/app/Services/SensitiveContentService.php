<?php

namespace App\Services;

use Illuminate\Support\Str;

class SensitiveContentService
{
    public function __construct(
        private readonly CommunitySettingsService $communitySettings,
    ) {}

    public function enabled(): bool
    {
        return $this->communitySettings->sensitiveWordsEnabled();
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

        $configuredTerms = collect($this->communitySettings->sensitiveWords())
            ->map(fn ($term): string => $this->normalizeText((string) $term))
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
            $text = $this->searchableText($value, (string) $field);

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

    private function searchableText(mixed $value, string $field): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) && $field === 'content_json') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $segments = [];
                $this->collectTextNodes($decoded, $segments);

                return $this->normalizeText(implode(' ', $segments));
            }
        }

        if (is_array($value)) {
            $segments = [];

            if ($field === 'content_json') {
                $this->collectTextNodes($value, $segments);
            } else {
                $this->collectScalarText($value, $segments);
            }

            return $this->normalizeText(implode(' ', $segments));
        }

        if (! is_scalar($value)) {
            return '';
        }

        return $this->normalizeText((string) $value);
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function collectTextNodes(mixed $node, array &$segments): void
    {
        if (! is_array($node)) {
            return;
        }

        if (isset($node['text']) && is_string($node['text'])) {
            $segments[] = $node['text'];
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectTextNodes($value, $segments);
            }
        }
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function collectScalarText(array $items, array &$segments): void
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->collectScalarText($item, $segments);

                continue;
            }

            if (is_scalar($item)) {
                $segments[] = (string) $item;
            }
        }
    }

    private function normalizeText(string $value): string
    {
        return Str::of(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->lower()
            ->squish()
            ->value();
    }
}
