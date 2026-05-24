<?php

namespace App\Support;

use App\Models\B2BLead;
use App\Models\HomeSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class LeadFormCustomFields
{
    private const SUPPORTED_TYPES = ['text', 'textarea', 'select', 'checkbox'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function schemaForSourcePage(?string $sourcePage): array
    {
        $pageKey = self::pageKeyFromSource($sourcePage);

        if ($pageKey === null) {
            return [];
        }

        $section = HomeSection::query()
            ->where('page_key', $pageKey)
            ->where('key', 'form')
            ->published()
            ->first();

        return self::schemaFromSection($section);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function schemaFromSection(?HomeSection $section): array
    {
        $payload = $section?->payload;
        $fields = is_array($payload) ? ($payload['custom_fields'] ?? []) : [];

        if (! is_array($fields)) {
            return [];
        }

        return collect($fields)
            ->filter(fn (mixed $field): bool => is_array($field) && filled($field['key'] ?? null))
            ->map(fn (array $field): array => self::normalizeField($field))
            ->filter(fn (array $field): bool => in_array($field['type'], self::SUPPORTED_TYPES, true))
            ->sortBy(fn (array $field): int => (int) ($field['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    public static function validate(array $payload, Validator $validator): void
    {
        $schema = self::schemaForSourcePage((string) ($payload['source_page'] ?? ''));

        if ($schema === []) {
            return;
        }

        $submitted = Arr::get($payload, 'metadata.custom_fields', []);

        if ($submitted !== [] && ! is_array($submitted)) {
            $validator->errors()->add('metadata.custom_fields', 'Custom fields must be an object.');

            return;
        }

        $schemaByKey = collect($schema)->keyBy('key');

        foreach (array_keys($submitted) as $key) {
            if (! $schemaByKey->has($key)) {
                $validator->errors()->add("metadata.custom_fields.{$key}", 'This custom field is not configured.');
            }
        }

        foreach ($schema as $field) {
            $key = (string) $field['key'];
            $value = $submitted[$key] ?? null;
            $label = self::label($field);

            if (($field['required'] ?? false) && self::blankValue($value)) {
                $validator->errors()->add("metadata.custom_fields.{$key}", "{$label} is required.");

                continue;
            }

            if (self::blankValue($value)) {
                continue;
            }

            self::validateValue($validator, $key, $label, $field, $value);
        }
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    public static function displayForLead(B2BLead $lead): array
    {
        $values = Arr::get($lead->metadata ?? [], 'custom_fields', []);

        if (! is_array($values) || $values === []) {
            return [];
        }

        $schema = collect(self::schemaForSourcePage($lead->source_page))->keyBy('key');

        return collect($values)
            ->map(function (mixed $value, mixed $key) use ($schema): array {
                $key = (string) $key;
                $field = $schema->get($key);

                return [
                    'key' => $key,
                    'label' => is_array($field) ? self::label($field) : Str::headline($key),
                    'value' => self::displayValue($value, is_array($field) ? $field : null),
                ];
            })
            ->filter(fn (array $field): bool => $field['value'] !== '')
            ->values()
            ->all();
    }

    public static function renderTextForLead(B2BLead $lead): string
    {
        return collect(self::displayForLead($lead))
            ->map(fn (array $field): string => "{$field['label']}: {$field['value']}")
            ->implode("\n");
    }

    /**
     * @return array<string, string>
     */
    public static function associativeDisplayForLead(B2BLead $lead): array
    {
        return collect(self::displayForLead($lead))
            ->mapWithKeys(fn (array $field): array => [$field['label'] => $field['value']])
            ->all();
    }

    public static function renderedBlockForLead(B2BLead $lead): string
    {
        $lines = collect(self::displayForLead($lead))
            ->map(fn (array $field): string => "- {$field['label']}: {$field['value']}")
            ->implode("\n");

        return $lines === '' ? '' : "Custom fields:\n{$lines}";
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private static function normalizeField(array $field): array
    {
        return [
            ...$field,
            'key' => (string) $field['key'],
            'type' => in_array(($field['type'] ?? null), self::SUPPORTED_TYPES, true) ? (string) $field['type'] : 'text',
            'required' => (bool) ($field['required'] ?? false),
            'sort_order' => (int) ($field['sort_order'] ?? 0),
            'options' => self::normalizeOptions($field['options'] ?? []),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->filter(fn (mixed $option): bool => is_array($option) && filled($option['value'] ?? null))
            ->map(fn (array $option): array => [
                ...$option,
                'value' => (string) $option['value'],
                'sort_order' => (int) ($option['sort_order'] ?? 0),
            ])
            ->sortBy(fn (array $option): int => (int) $option['sort_order'])
            ->values()
            ->all();
    }

    private static function validateValue(Validator $validator, string $key, string $label, array $field, mixed $value): void
    {
        $path = "metadata.custom_fields.{$key}";
        $type = $field['type'];

        if ($type === 'checkbox') {
            $allowed = self::optionValues($field);

            if ($allowed !== []) {
                if (! is_array($value)) {
                    $validator->errors()->add($path, "{$label} must be a list of selected options.");

                    return;
                }

                foreach ($value as $selected) {
                    if (! is_string($selected) || ! in_array($selected, $allowed, true)) {
                        $validator->errors()->add($path, "{$label} contains an invalid option.");

                        return;
                    }
                }

                return;
            }

            if (! is_bool($value) && ! is_string($value) && ! is_numeric($value)) {
                $validator->errors()->add($path, "{$label} must be a checkbox value.");
            }

            return;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $validator->errors()->add($path, "{$label} must be text.");

            return;
        }

        $value = trim((string) $value);
        $max = $type === 'textarea' ? 5000 : 500;

        if (Str::length($value) > $max) {
            $validator->errors()->add($path, "{$label} must be {$max} characters or fewer.");

            return;
        }

        if ($type === 'select') {
            $allowed = self::optionValues($field);

            if ($allowed !== [] && ! in_array($value, $allowed, true)) {
                $validator->errors()->add($path, "{$label} contains an invalid option.");
            }
        }
    }

    private static function blankValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private static function optionValues(array $field): array
    {
        return collect($field['options'] ?? [])
            ->pluck('value')
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    private static function displayValue(mixed $value, ?array $field): string
    {
        $labels = is_array($field) ? self::optionLabelMap($field) : [];

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): string => self::displayScalar($item, $labels))
                ->filter()
                ->implode(', ');
        }

        return self::displayScalar($value, $labels);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, string>
     */
    private static function optionLabelMap(array $field): array
    {
        return collect($field['options'] ?? [])
            ->filter(fn (mixed $option): bool => is_array($option) && is_string($option['value'] ?? null))
            ->mapWithKeys(fn (array $option): array => [(string) $option['value'] => self::label($option, (string) $option['value'])])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function label(array $field, ?string $fallback = null): string
    {
        $locale = app()->getLocale();
        $translations = $field['label_translations'] ?? null;

        if (is_array($translations)) {
            foreach ([$locale, 'en'] as $candidate) {
                if (is_string($translations[$candidate] ?? null) && trim($translations[$candidate]) !== '') {
                    return trim($translations[$candidate]);
                }
            }
        }

        if (is_string($field['label'] ?? null) && trim($field['label']) !== '') {
            return trim((string) $field['label']);
        }

        return Str::headline($fallback ?: (string) ($field['key'] ?? 'Custom field'));
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function displayScalar(mixed $value, array $labels): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null) {
            return '';
        }

        if (! is_scalar($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        $string = trim((string) $value);

        return $labels[$string] ?? $string;
    }

    private static function pageKeyFromSource(?string $sourcePage): ?string
    {
        $sourcePage = strtolower(trim((string) $sourcePage));

        if ($sourcePage === '') {
            return null;
        }

        $pageKey = Str::before($sourcePage, ':');

        return in_array($pageKey, ['contact', 'b2b'], true) ? $pageKey : null;
    }
}
