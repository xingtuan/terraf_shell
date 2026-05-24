<?php

namespace App\Support;

use App\Enums\PublishStatus;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialCmsToHomeSectionsMigrator
{
    private const MATERIAL_PAGE_KEY = 'material';

    /**
     * @return array{created: int, updated: int, skipped: array<int, string>}
     */
    public function migrate(bool $force = false): array
    {
        return DB::transaction(function () use ($force): array {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'skipped' => [],
            ];

            $material = $this->representativeMaterial();

            if ($material instanceof Material) {
                $this->migrateIntro($material, $force, $summary);
                $this->migrateCertifications($material, $force, $summary);
                $this->migrateTechnicalDownloads($material, $force, $summary);
            }

            $this->migrateStorySections($force, $summary);
            $this->migrateApplications($force, $summary);
            $this->migrateSpecs($force, $summary);

            return $summary;
        });
    }

    private function representativeMaterial(): ?Material
    {
        return Material::query()
            ->where('status', PublishStatus::Published->value)
            ->orderByDesc('is_featured')
            ->ordered()
            ->first()
            ?: Material::query()
                ->orderByDesc('is_featured')
                ->ordered()
                ->first();
    }

    /**
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function migrateIntro(Material $material, bool $force, array &$summary): void
    {
        $this->upsertSection([
            'key' => 'intro',
            'title' => $material->title,
            'title_translations' => $this->translations($material, 'title'),
            'subtitle' => $material->headline,
            'subtitle_translations' => $this->translations($material, 'headline'),
            'content' => $material->summary,
            'content_translations' => $this->translations($material, 'summary'),
            'payload' => [
                'variant' => 'intro',
                'material_slug' => $material->slug,
            ],
            'status' => $this->sectionStatus($material),
            'sort_order' => 1,
            'media_path' => $material->media_path,
            'media_url' => $material->media_url,
            'published_at' => $this->publishedAt($material),
        ], $force, $summary);
    }

    /**
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function migrateStorySections(bool $force, array &$summary): void
    {
        $items = MaterialStorySection::query()
            ->where('status', PublishStatus::Published->value)
            ->whereHas('material', fn ($query) => $query->where('status', PublishStatus::Published->value))
            ->with('material')
            ->orderBy('material_id')
            ->ordered()
            ->get()
            ->map(fn (MaterialStorySection $section): array => [
                'key' => $this->itemKey($section, 'story'),
                'title' => $section->title,
                'title_translations' => $this->translations($section, 'title'),
                'subtitle' => $section->subtitle,
                'subtitle_translations' => $this->translations($section, 'subtitle'),
                'content' => $section->content,
                'content_translations' => $this->translations($section, 'content'),
                'highlight' => $section->highlight,
                'highlight_translations' => $this->translations($section, 'highlight'),
                'media_path' => $section->media_path,
                'media_url' => $section->media_url,
                'sort_order' => $section->sort_order,
            ])
            ->values()
            ->all();

        if ($items === []) {
            return;
        }

        $material = $this->representativeMaterial();

        $this->upsertSection([
            'key' => 'material_story',
            'title' => 'Material story',
            'content' => $material?->story_overview,
            'content_translations' => $material instanceof Material ? $this->translations($material, 'story_overview') : [],
            'payload' => [
                'variant' => 'material_story',
                'items' => $items,
            ],
            'status' => PublishStatus::Published->value,
            'sort_order' => 4,
            'published_at' => now(),
        ], $force, $summary);
    }

    /**
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function migrateApplications(bool $force, array &$summary): void
    {
        $items = MaterialApplication::query()
            ->where('status', PublishStatus::Published->value)
            ->whereHas('material', fn ($query) => $query->where('status', PublishStatus::Published->value))
            ->with('material')
            ->orderBy('material_id')
            ->ordered()
            ->get()
            ->map(fn (MaterialApplication $application): array => [
                'key' => $this->itemKey($application, 'application'),
                'title' => $application->title,
                'title_translations' => $this->translations($application, 'title'),
                'subtitle' => $application->subtitle,
                'subtitle_translations' => $this->translations($application, 'subtitle'),
                'description' => $application->description,
                'description_translations' => $this->translations($application, 'description'),
                'audience' => $application->audience,
                'audience_translations' => $this->translations($application, 'audience'),
                'cta_label' => $application->cta_label,
                'cta_label_translations' => $this->translations($application, 'cta_label'),
                'cta_url' => $application->cta_url,
                'media_path' => $application->media_path,
                'media_url' => $application->media_url,
                'sort_order' => $application->sort_order,
            ])
            ->values()
            ->all();

        if ($items === []) {
            return;
        }

        $this->upsertSection([
            'key' => 'applications',
            'title' => 'Applications',
            'payload' => [
                'variant' => 'applications',
                'items' => $items,
            ],
            'status' => PublishStatus::Published->value,
            'sort_order' => 5,
            'published_at' => now(),
        ], $force, $summary);
    }

    /**
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function migrateSpecs(bool $force, array &$summary): void
    {
        $metrics = MaterialSpec::query()
            ->where('status', PublishStatus::Published->value)
            ->whereHas('material', fn ($query) => $query->where('status', PublishStatus::Published->value))
            ->with('material')
            ->orderBy('material_id')
            ->ordered()
            ->get()
            ->map(fn (MaterialSpec $spec): array => [
                'key' => $spec->key ?: $this->itemKey($spec, 'spec'),
                'label' => $spec->label,
                'label_translations' => $this->translations($spec, 'label'),
                'value' => $spec->value,
                'value_translations' => $this->translations($spec, 'value'),
                'detail' => $spec->detail,
                'detail_translations' => $this->translations($spec, 'detail'),
                'unit' => $spec->unit,
                'icon' => $this->materialFactIcon($spec->icon),
                'media_path' => $spec->media_path,
                'media_url' => $spec->media_url,
                'sort_order' => $spec->sort_order,
            ])
            ->values()
            ->all();

        if ($metrics === []) {
            return;
        }

        $material = $this->representativeMaterial();

        $this->upsertSection([
            'key' => 'material_facts',
            'title' => 'Material facts',
            'content' => $material?->science_overview,
            'content_translations' => $material instanceof Material ? $this->translations($material, 'science_overview') : [],
            'payload' => [
                'variant' => 'material_facts',
                'metrics' => $metrics,
            ],
            'status' => PublishStatus::Published->value,
            'sort_order' => 6,
            'published_at' => now(),
        ], $force, $summary);
    }

    /**
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function migrateCertifications(Material $material, bool $force, array &$summary): void
    {
        if (! $this->isPublished($material)) {
            return;
        }

        $items = collect(is_array($material->certifications) ? $material->certifications : [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item, int $index): array => $this->certificationItem($item, $index))
            ->values()
            ->all();

        if ($items === []) {
            return;
        }

        $this->upsertSection([
            'key' => 'certifications',
            'title' => 'Certifications',
            'payload' => [
                'variant' => 'certifications',
                'items' => $items,
            ],
            'status' => PublishStatus::Published->value,
            'sort_order' => 8,
            'published_at' => $this->publishedAt($material),
        ], $force, $summary);
    }

    /**
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function migrateTechnicalDownloads(Material $material, bool $force, array &$summary): void
    {
        if (! $this->isPublished($material)) {
            return;
        }

        $downloads = collect(is_array($material->technical_downloads) ? $material->technical_downloads : [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item, int $index): array => $this->downloadItem($item, $index))
            ->values()
            ->all();

        if ($downloads === []) {
            return;
        }

        $this->upsertSection([
            'key' => 'technical_downloads',
            'title' => 'Technical downloads',
            'payload' => [
                'variant' => 'technical_downloads',
                'downloads' => $downloads,
            ],
            'status' => PublishStatus::Published->value,
            'sort_order' => 9,
            'published_at' => $this->publishedAt($material),
        ], $force, $summary);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array{created: int, updated: int, skipped: array<int, string>}  $summary
     */
    private function upsertSection(array $attributes, bool $force, array &$summary): void
    {
        $key = (string) $attributes['key'];

        /** @var HomeSection|null $existing */
        $existing = HomeSection::query()
            ->where('page_key', self::MATERIAL_PAGE_KEY)
            ->where('key', $key)
            ->first();

        if ($existing instanceof HomeSection && ! $force && ! $existing->is_seeded) {
            $summary['skipped'][] = "material:{$key}";

            return;
        }

        $attributes = array_merge([
            'page_key' => self::MATERIAL_PAGE_KEY,
            'title' => null,
            'title_translations' => [],
            'subtitle' => null,
            'subtitle_translations' => [],
            'content' => null,
            'content_translations' => [],
            'cta_label' => null,
            'cta_label_translations' => [],
            'cta_url' => null,
            'payload' => [],
            'media_path' => null,
            'media_url' => null,
            'published_at' => null,
        ], $attributes, [
            'page_key' => self::MATERIAL_PAGE_KEY,
            'is_seeded' => false,
        ]);

        if ($attributes['status'] === PublishStatus::Published->value && blank($attributes['published_at'])) {
            $attributes['published_at'] = now();
        }

        HomeSection::query()->updateOrCreate([
            'page_key' => self::MATERIAL_PAGE_KEY,
            'key' => $key,
        ], $attributes);

        $summary[$existing instanceof HomeSection ? 'updated' : 'created']++;
    }

    /**
     * @return array<string, string>
     */
    private function translations(Model $model, string $field): array
    {
        $translations = $model->getAttribute("{$field}_translations");

        if (! is_array($translations)) {
            $translations = [];
        }

        $fallback = $model->getAttribute($field);

        if (is_string($fallback) && trim($fallback) !== '' && blank($translations['en'] ?? null)) {
            $translations['en'] = $fallback;
        }

        return $this->localizedStrings($translations);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    private function localizedStrings(array $translations): array
    {
        $localized = [];

        foreach (LocalizedContent::supportedLocales() as $locale) {
            $value = $translations[$locale] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $localized[$locale] = trim($value);
            }
        }

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function certificationItem(array $item, int $index): array
    {
        $normalized = [
            'key' => $this->arrayString($item, 'key') ?: "certification-{$index}",
            'status' => $this->arrayString($item, 'status') ?: 'pending',
            'verified' => (bool) ($item['verified'] ?? false),
            'unit' => $this->arrayString($item, 'unit'),
            'tested_at' => $this->arrayString($item, 'tested_at'),
            'document_url' => $this->arrayString($item, 'document_url'),
            'media_path' => $this->arrayString($item, 'media_path'),
            'media_url' => $this->arrayString($item, 'media_url'),
        ];

        foreach (['name', 'label', 'value', 'result', 'description', 'status', 'issuer'] as $field) {
            if ($value = $this->arrayString($item, $field)) {
                $normalized[$field] = $value;
            }

            $translations = $this->localizedStrings(is_array($item["{$field}_translations"] ?? null) ? $item["{$field}_translations"] : []);

            if ($translations !== []) {
                $normalized["{$field}_translations"] = $translations;
            } elseif (isset($normalized[$field]) && is_string($normalized[$field])) {
                $normalized["{$field}_translations"] = ['en' => $normalized[$field]];
            }
        }

        return array_filter(
            $normalized,
            fn (mixed $value): bool => ! ($value === null || $value === '' || $value === [])
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function downloadItem(array $item, int $index): array
    {
        $normalized = [
            'key' => $this->arrayString($item, 'key') ?: "download-{$index}",
            'type' => $this->arrayString($item, 'type') ?: 'document',
            'status' => $this->arrayString($item, 'status'),
            'url' => $this->arrayString($item, 'url'),
            'document_url' => $this->arrayString($item, 'document_url'),
            'file_path' => $this->arrayString($item, 'file_path'),
            'media_path' => $this->arrayString($item, 'media_path'),
            'media_url' => $this->arrayString($item, 'media_url'),
        ];

        foreach (['title', 'description'] as $field) {
            if ($value = $this->arrayString($item, $field)) {
                $normalized[$field] = $value;
            }

            $translations = $this->localizedStrings(is_array($item["{$field}_translations"] ?? null) ? $item["{$field}_translations"] : []);

            if ($translations !== []) {
                $normalized["{$field}_translations"] = $translations;
            } elseif (isset($normalized[$field]) && is_string($normalized[$field])) {
                $normalized["{$field}_translations"] = ['en' => $normalized[$field]];
            }
        }

        return array_filter(
            $normalized,
            fn (mixed $value): bool => ! ($value === null || $value === '' || $value === [])
        );
    }

    private function arrayString(array $item, string $key): ?string
    {
        $value = $item[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function itemKey(Model $model, string $prefix): string
    {
        $title = $model->getAttribute('title') ?: $model->getAttribute('label') ?: $prefix;

        return Str::slug((string) $title) ?: "{$prefix}-{$model->getKey()}";
    }

    private function materialFactIcon(?string $icon): string
    {
        return in_array($icon, ['feather', 'shield', 'leaf', 'badge'], true)
            ? $icon
            : 'badge';
    }

    private function sectionStatus(Model $model): string
    {
        return $this->isPublished($model)
            ? PublishStatus::Published->value
            : PublishStatus::Draft->value;
    }

    private function isPublished(Model $model): bool
    {
        return PublishStatus::normalize($model->getAttribute('status')) === PublishStatus::Published;
    }

    private function publishedAt(Model $model): mixed
    {
        return $this->isPublished($model) ? ($model->getAttribute('published_at') ?: now()) : null;
    }
}
