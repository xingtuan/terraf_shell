<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELD_KEY_MAP = [
        'company' => 'companyName',
        'organization_type' => 'organizationType',
        'company_website' => 'companyWebsite',
        'job_title' => 'jobTitle',
        'material_interest' => 'materialInterest',
        'quantity_estimate' => 'quantityEstimate',
        'shipping_country' => 'shippingCountry',
        'shipping_region' => 'shippingRegion',
        'shipping_address' => 'shippingAddress',
        'intended_use' => 'intendedUse',
        'collaboration_goal' => 'collaborationGoal',
        'project_stage' => 'projectStage',
    ];

    private const FIELD_ORDER = [
        'name',
        'companyName',
        'organizationType',
        'email',
        'phone',
        'country',
        'region',
        'companyWebsite',
        'jobTitle',
        'application',
        'volume',
        'timeline',
        'message',
        'collaborationGoal',
        'projectStage',
        'materialInterest',
        'quantityEstimate',
        'shippingCountry',
        'shippingRegion',
        'shippingAddress',
        'intendedUse',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('home_sections') || ! Schema::hasColumn('home_sections', 'page_key')) {
            return;
        }

        $this->normalizeSectionPayload('b2b', 'form');
        $this->canonicalizeContactForm();
        $this->normalizeSectionPayload('contact', 'form');
    }

    public function down(): void
    {
        //
    }

    private function canonicalizeContactForm(): void
    {
        $legacy = $this->section('contact', 'inquiry_form');
        $canonical = $this->section('contact', 'form');

        if (! $legacy) {
            return;
        }

        $legacyPayload = $this->normalizePayload($this->decodePayload($legacy->payload ?? null));

        if (! $canonical) {
            DB::table('home_sections')
                ->where('id', $legacy->id)
                ->update([
                    'key' => 'form',
                    'payload' => $this->encodePayload($legacyPayload),
                    'updated_at' => now(),
                ]);

            return;
        }

        $canonicalPayload = $this->normalizePayload($this->decodePayload($canonical->payload ?? null));
        $updates = [
            'payload' => $this->encodePayload($this->mergeMissing($canonicalPayload, $legacyPayload)),
            'updated_at' => now(),
        ];

        foreach ([
            'title',
            'title_translations',
            'subtitle',
            'subtitle_translations',
            'content',
            'content_translations',
            'cta_label',
            'cta_label_translations',
            'cta_url',
        ] as $column) {
            $current = $canonical->{$column} ?? null;
            $incoming = $legacy->{$column} ?? null;

            if ($this->isMissing($current) && ! $this->isMissing($incoming)) {
                $updates[$column] = $incoming;
            }
        }

        DB::table('home_sections')
            ->where('id', $canonical->id)
            ->update($updates);

        DB::table('home_sections')
            ->where('id', $legacy->id)
            ->update([
                'status' => 'archived',
                'updated_at' => now(),
            ]);
    }

    private function normalizeSectionPayload(string $pageKey, string $key): void
    {
        $section = $this->section($pageKey, $key);

        if (! $section) {
            return;
        }

        DB::table('home_sections')
            ->where('id', $section->id)
            ->update([
                'payload' => $this->encodePayload($this->normalizePayload($this->decodePayload($section->payload ?? null))),
                'updated_at' => now(),
            ]);
    }

    private function section(string $pageKey, string $key): ?object
    {
        return DB::table('home_sections')
            ->where('page_key', $pageKey)
            ->where('key', $key)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(mixed $payload): array
    {
        $payload = is_array($payload) ? $payload : [];

        foreach (['fields', 'placeholders', 'helpers'] as $container) {
            if (isset($payload[$container]) && is_array($payload[$container])) {
                $payload[$container] = $this->normalizeFieldTranslationMap($payload[$container]);
            }
        }

        if (isset($payload['field_settings']) && is_array($payload['field_settings'])) {
            $payload['field_settings'] = array_values(array_map(
                fn (array $setting): array => $this->normalizeFieldSetting($setting),
                array_filter($payload['field_settings'], 'is_array')
            ));
        }

        if (empty($payload['field_settings'])) {
            $payload['field_settings'] = $this->defaultFieldSettings();
        }

        if (empty($payload['interest_options']) && ! empty($payload['topic_options'])) {
            $payload['interest_options'] = $this->topicOptionsToInterestOptions($payload['topic_options']);
        }

        unset($payload['topic_options']);

        if (! isset($payload['custom_fields']) || ! is_array($payload['custom_fields'])) {
            $payload['custom_fields'] = [];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function normalizeFieldTranslationMap(array $map): array
    {
        foreach (self::FIELD_KEY_MAP as $legacy => $stable) {
            foreach (['', '_translations'] as $suffix) {
                $legacyKey = $legacy.$suffix;
                $stableKey = $stable.$suffix;

                if (array_key_exists($legacyKey, $map) && ! array_key_exists($stableKey, $map)) {
                    $map[$stableKey] = $map[$legacyKey];
                }

                unset($map[$legacyKey]);
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $setting
     * @return array<string, mixed>
     */
    private function normalizeFieldSetting(array $setting): array
    {
        $key = (string) ($setting['key'] ?? '');

        if (array_key_exists($key, self::FIELD_KEY_MAP)) {
            $key = self::FIELD_KEY_MAP[$key];
        }

        return [
            ...$setting,
            'key' => $key,
            'visible' => array_key_exists('visible', $setting) ? (bool) $setting['visible'] : true,
            'required' => array_key_exists('required', $setting) ? (bool) $setting['required'] : false,
            'sort_order' => (int) ($setting['sort_order'] ?? $setting['order'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultFieldSettings(): array
    {
        $required = ['name', 'companyName', 'email', 'application', 'message'];

        return array_values(array_map(
            fn (string $field, int $index): array => [
                'key' => $field,
                'visible' => true,
                'required' => in_array($field, $required, true),
                'sort_order' => ($index + 1) * 10,
            ],
            self::FIELD_ORDER,
            array_keys(self::FIELD_ORDER)
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topicOptionsToInterestOptions(mixed $topicOptions): array
    {
        $defaults = [
            ['id' => 'inquiry', 'interest_type' => 'pellet_supply'],
            ['id' => 'sample_request', 'interest_type' => 'sample_request'],
            ['id' => 'product_development_collaboration', 'interest_type' => 'product_development'],
            ['id' => 'bulk_order', 'interest_type' => 'bulk_order'],
            ['id' => 'partnership_inquiry', 'interest_type' => 'partnership'],
            ['id' => 'other', 'interest_type' => 'other'],
        ];

        $topics = is_array($topicOptions) ? array_values($topicOptions) : [];

        $lastTopic = $topics === [] ? null : $topics[array_key_last($topics)];

        return array_values(array_map(function (array $default, int $index) use ($topics, $defaults, $lastTopic): array {
            $topic = $topics[$index] ?? ($index === count($defaults) - 1 ? $lastTopic : null);

            if (is_array($topic)) {
                $labelTranslations = $topic['label_translations'] ?? null;
                $label = $topic['label'] ?? null;
            } else {
                $labelTranslations = null;
                $label = $topic;
            }

            return array_filter([
                ...$default,
                'label' => is_string($label) ? $label : null,
                'label_translations' => is_array($labelTranslations) ? $labelTranslations : null,
            ], fn (mixed $value): bool => $value !== null && $value !== []);
        }, $defaults, array_keys($defaults)));
    }

    private function decodePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function mergeMissing(mixed $current, mixed $incoming): mixed
    {
        if ($this->isMissing($current)) {
            return $incoming;
        }

        if (! is_array($current) || ! is_array($incoming)) {
            return $current;
        }

        if (array_is_list($current) || array_is_list($incoming)) {
            return $current === [] ? $incoming : $current;
        }

        $merged = $current;

        foreach ($incoming as $key => $incomingValue) {
            $merged[$key] = array_key_exists($key, $merged)
                ? $this->mergeMissing($merged[$key], $incomingValue)
                : $incomingValue;
        }

        return $merged;
    }

    private function isMissing(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && $value === []);
    }
};
