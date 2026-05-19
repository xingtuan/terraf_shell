<?php

namespace App\Support;

use App\Enums\PublishStatus;

class DefaultPageSections
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function records(): array
    {
        $messages = self::messages();

        if ($messages === []) {
            return [];
        }

        return [
            ...self::homeRecords($messages),
            ...self::materialRecords($messages),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function databaseRows(): array
    {
        $now = now();

        return array_map(
            fn (array $record): array => [
                'page_key' => $record['page_key'],
                'key' => $record['key'],
                'title' => $record['title'],
                'title_translations' => json_encode($record['title_translations'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'subtitle' => $record['subtitle'],
                'subtitle_translations' => json_encode($record['subtitle_translations'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'content' => $record['content'],
                'content_translations' => json_encode($record['content_translations'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'cta_label' => $record['cta_label'],
                'cta_label_translations' => $record['cta_label_translations'] === []
                    ? null
                    : json_encode($record['cta_label_translations'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'cta_url' => $record['cta_url'],
                'payload' => json_encode($record['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'is_seeded' => true,
                'status' => PublishStatus::Published->value,
                'sort_order' => $record['sort_order'],
                'media_path' => null,
                'media_url' => $record['media_url'] ?? null,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::records()
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function homeRecords(array $messages): array
    {
        return [
            self::record($messages, 'home', 'hero', 'home.hero.title', 'home.hero.eyebrow', 'home.hero.description', 'home.hero.primaryCta', 'material', [
                'variant' => 'hero',
                'secondary_cta_label_translations' => self::translations($messages, 'home.hero.secondaryCta'),
                'secondary_cta_url' => 'b2b',
                'metrics' => self::stringItems($messages, 'home.hero.indicators', 'label'),
            ], 1, '/images/hero-material.jpg'),
            self::record($messages, 'home', 'audience_paths', 'home.audiencePaths.title', 'home.audiencePaths.eyebrow', null, null, null, [
                'variant' => 'cards',
                'items' => self::cardItems($messages, 'home.audiencePaths.cards', [
                    'label' => 'label',
                    'title' => 'title',
                    'description' => 'description',
                    'cta_label' => 'cta',
                    'cta_url' => 'href',
                ]),
            ], 2),
            self::record($messages, 'home', 'business_pillars', 'home.businessPillars.title', 'home.businessPillars.eyebrow', null, null, null, [
                'variant' => 'pillars',
                'items' => self::cardItems($messages, 'home.businessPillars.pillars', [
                    'title' => 'name',
                    'subtitle' => 'formula',
                    'description' => 'description',
                ]),
            ], 3),
            self::record($messages, 'home', 'why_it_matters', 'home.whyItMatters.title', 'home.whyItMatters.eyebrow', null, null, null, [
                'variant' => 'why_it_matters',
                'items' => self::cardItems($messages, 'home.whyItMatters.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'metrics' => self::stringItems($messages, 'home.whyItMatters.stats', 'description'),
            ], 4),
            self::record($messages, 'home', 'material_story', 'home.materialStory.title', 'home.materialStory.eyebrow', null, null, null, [
                'variant' => 'material_story',
                'items' => self::cardItems($messages, 'home.materialStory.steps', [
                    'label' => 'number',
                    'title' => 'title',
                    'description' => 'description',
                ], ['/images/process-collected.jpg', '/images/process-refined.jpg', '/images/process-recrafted.jpg', '/images/application-tableware.jpg']),
            ], 5),
            self::record($messages, 'home', 'open_source_legacy', 'home.openSourceLegacy.title', 'home.openSourceLegacy.eyebrow', 'home.openSourceLegacy.intro', null, null, [
                'variant' => 'legacy',
                'items' => self::cardItems($messages, 'home.openSourceLegacy.authors', [
                    'title' => 'author',
                    'subtitle' => 'timeframe',
                    'label' => 'sourceCode',
                    'description' => 'legacy',
                ]),
            ], 6),
            self::record($messages, 'home', 'applications', 'home.applications.title', 'home.applications.eyebrow', null, null, null, [
                'variant' => 'applications',
                'items' => self::cardItems($messages, 'home.applications.items', [
                    'title' => 'title',
                    'description' => 'description',
                ], ['/images/application-tableware.jpg', '/images/application-interior.jpg', '/images/application-packaging.jpg', '/images/application-retail.jpg']),
            ], 7),
            self::record($messages, 'home', 'science_block', 'home.materialFacts.title', 'home.materialFacts.eyebrow', 'home.materialFacts.sheetDescription', 'home.materialFacts.sheetCta', 'b2b?leadType=sample_request#inquiry', [
                'variant' => 'science',
                'sheet_title_translations' => self::translations($messages, 'home.materialFacts.sheetTitle'),
                'note_translations' => self::translations($messages, 'home.materialFacts.note'),
                'metrics' => self::cardItems($messages, 'home.materialFacts.infoCards', [
                    'label' => 'label',
                    'value' => 'value',
                ]),
                'material_slug' => 'premium-oyster-shell',
            ], 8),
            self::record($messages, 'home', 'collaboration', 'home.collaboration.title', 'home.collaboration.eyebrow', null, null, null, [
                'variant' => 'collaboration',
                'items' => self::cardItems($messages, 'home.collaboration.cards', [
                    'title' => 'title',
                    'subtitle' => 'forWhom',
                    'description' => 'description',
                    'cta_label' => 'cta',
                ], [], ['b2b#inquiry', 'b2b?leadType=sample_request#inquiry', 'b2b?leadType=product_development_collaboration#inquiry']),
                'process_title_translations' => self::translations($messages, 'home.collaboration.processTitle'),
                'steps' => self::stringItems($messages, 'home.collaboration.steps', 'label'),
            ], 9),
            self::record($messages, 'home', 'credibility', 'home.credibility.title', 'home.credibility.eyebrow', null, null, null, [
                'variant' => 'credibility',
                'items' => self::cardItems($messages, 'home.credibility.features', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'metrics' => self::stringItems($messages, 'home.credibility.benefits', 'description'),
            ], 10, '/images/material-texture.jpg'),
            self::record($messages, 'home', 'trust_and_credibility', 'trustAndCredibility.title', 'trustAndCredibility.eyebrow', 'trustAndCredibility.description', null, null, [
                'variant' => 'trust',
                'items' => self::cardItems($messages, 'trustAndCredibility.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'disclaimer_translations' => self::translations($messages, 'trustAndCredibility.disclaimer'),
            ], 11),
            self::record($messages, 'home', 'pilot_projects', 'pilotProjects.title', 'pilotProjects.eyebrow', 'pilotProjects.description', null, null, [
                'variant' => 'pilot_projects',
                'items' => self::cardItems($messages, 'pilotProjects.items', [
                    'title' => 'title',
                    'status' => 'status',
                    'description' => 'description',
                ]),
            ], 12),
            self::record($messages, 'home', 'latest_updates', 'articleFeed.defaultTitle', 'articleFeed.defaultEyebrow', 'articleFeed.defaultDescription', 'articleFeed.defaultCta', 'articles', [
                'variant' => 'article_feed',
                'limit' => 3,
            ], 13),
            self::record($messages, 'home', 'final_cta', 'home.finalCta.title', null, 'home.finalCta.description', null, null, [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::translations($messages, 'home.finalCta.primaryCta'),
                'primary_cta_url' => 'b2b#inquiry',
                'secondary_cta_label_translations' => self::translations($messages, 'home.finalCta.secondaryCta'),
                'secondary_cta_url' => 'store',
            ], 14),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function materialRecords(array $messages): array
    {
        return [
            self::record($messages, 'material', 'intro', 'materialPage.intro.title', 'materialPage.intro.eyebrow', 'materialPage.intro.description', 'materialPage.intro.primaryCta', 'b2b?leadType=sample_request#inquiry', [
                'variant' => 'intro',
                'secondary_cta_label_translations' => self::translations($messages, 'materialPage.intro.secondaryCta'),
                'secondary_cta_url' => 'contact',
            ], 1),
            self::record($messages, 'material', 'material_family', 'home.materialFamily.title', 'home.materialFamily.eyebrow', 'home.materialFamily.intro', null, null, [
                'variant' => 'material_family',
                'diagram' => [
                    'title_translations' => self::translations($messages, 'home.materialFamily.diagram.title'),
                    'alt_translations' => self::translations($messages, 'home.materialFamily.diagram.alt'),
                    'caption_translations' => self::translations($messages, 'home.materialFamily.diagram.caption'),
                    'media_url' => '/images/terraf_en.jpg',
                    'media_url_ko' => '/images/terraf_ko.jpg',
                ],
                'legend' => self::cardItems($messages, 'home.materialFamily.legend', [
                    'label' => 'label',
                    'description' => 'description',
                ]),
                'badges' => [
                    'current_translations' => self::translations($messages, 'home.materialFamily.badges.current'),
                    'sibling_translations' => self::translations($messages, 'home.materialFamily.badges.sibling'),
                    'inactive_translations' => self::translations($messages, 'home.materialFamily.badges.inactive'),
                ],
                'items' => self::cardItems($messages, 'home.materialFamily.lines', [
                    'key' => 'code',
                    'title' => 'name',
                    'subtitle' => 'source',
                    'description' => 'description',
                    'status' => 'status',
                ]),
            ], 2, '/images/terraf_en.jpg'),
            self::record($messages, 'material', 'why_it_matters', 'home.whyItMatters.title', 'home.whyItMatters.eyebrow', null, null, null, [
                'variant' => 'why_it_matters',
                'items' => self::cardItems($messages, 'home.whyItMatters.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'metrics' => self::stringItems($messages, 'home.whyItMatters.stats', 'description'),
            ], 3),
            self::record($messages, 'material', 'material_story', 'home.materialStory.title', 'home.materialStory.eyebrow', null, null, null, [
                'variant' => 'material_story',
                'items' => self::cardItems($messages, 'home.materialStory.steps', [
                    'label' => 'number',
                    'title' => 'title',
                    'description' => 'description',
                ], ['/images/process-collected.jpg', '/images/process-refined.jpg', '/images/process-recrafted.jpg', '/images/application-tableware.jpg']),
            ], 4),
            self::record($messages, 'material', 'open_source_legacy', 'home.openSourceLegacy.title', 'home.openSourceLegacy.eyebrow', 'home.openSourceLegacy.intro', null, null, [
                'variant' => 'legacy',
                'items' => self::cardItems($messages, 'home.openSourceLegacy.authors', [
                    'title' => 'author',
                    'subtitle' => 'timeframe',
                    'label' => 'sourceCode',
                    'description' => 'legacy',
                ]),
            ], 5),
            self::record($messages, 'material', 'applications', 'home.applications.title', 'home.applications.eyebrow', null, null, null, [
                'variant' => 'applications',
                'items' => self::cardItems($messages, 'home.applications.items', [
                    'title' => 'title',
                    'description' => 'description',
                ], ['/images/application-tableware.jpg', '/images/application-interior.jpg', '/images/application-packaging.jpg', '/images/application-retail.jpg']),
            ], 6),
            self::record($messages, 'material', 'material_facts', 'home.materialFacts.title', 'home.materialFacts.eyebrow', 'home.materialFacts.sheetDescription', 'home.materialFacts.sheetCta', 'b2b?leadType=sample_request#inquiry', [
                'variant' => 'material_facts',
                'sheet_title_translations' => self::translations($messages, 'home.materialFacts.sheetTitle'),
                'note_translations' => self::translations($messages, 'home.materialFacts.note'),
                'metrics' => self::cardItems($messages, 'home.materialFacts.infoCards', [
                    'label' => 'label',
                    'value' => 'value',
                ]),
            ], 7),
            self::record($messages, 'material', 'proof_points', 'materialProof.proofPoints.title', 'materialProof.proofPoints.eyebrow', 'materialProof.proofPoints.description', null, null, [
                'variant' => 'proof_points',
                'items' => self::cardItems($messages, 'materialProof.proofPoints.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
            ], 8),
            self::record($messages, 'material', 'certifications', 'certificationsAtAGlance.title', 'certificationsAtAGlance.eyebrow', 'certificationsAtAGlance.description', null, null, [
                'variant' => 'certifications',
                'verified_label_translations' => self::translations($messages, 'certificationsAtAGlance.verifiedLabel'),
                'empty_message_translations' => self::translations($messages, 'certificationsAtAGlance.emptyMessage'),
                'issuer_label_translations' => self::translations($messages, 'certificationsAtAGlance.issuerLabel'),
                'tested_at_label_translations' => self::translations($messages, 'certificationsAtAGlance.testedAtLabel'),
                'download_label_translations' => self::translations($messages, 'certificationsAtAGlance.downloadLabel'),
                'status_labels' => [
                    'certified_translations' => self::translations($messages, 'certificationsAtAGlance.statusLabels.certified'),
                    'tested_translations' => self::translations($messages, 'certificationsAtAGlance.statusLabels.tested'),
                    'in_testing_translations' => self::translations($messages, 'certificationsAtAGlance.statusLabels.in_testing'),
                    'pending_translations' => self::translations($messages, 'certificationsAtAGlance.statusLabels.pending'),
                    'demo_translations' => self::translations($messages, 'certificationsAtAGlance.statusLabels.demo'),
                    'not_applicable_translations' => self::translations($messages, 'certificationsAtAGlance.statusLabels.not_applicable'),
                ],
            ], 9),
            self::record($messages, 'material', 'technical_downloads', 'materialProof.technicalDownloads.title', 'materialProof.technicalDownloads.eyebrow', 'materialProof.technicalDownloads.description', 'materialProof.technicalDownloads.downloadLabel', null, [
                'variant' => 'technical_downloads',
                'empty_title_translations' => self::translations($messages, 'materialProof.technicalDownloads.emptyTitle'),
                'empty_description_translations' => self::translations($messages, 'materialProof.technicalDownloads.emptyDescription'),
                'file_label_translations' => self::translations($messages, 'materialProof.technicalDownloads.fileLabel'),
                'on_request_label_translations' => self::translations($messages, 'materialProof.technicalDownloads.onRequestLabel'),
                'downloads' => [],
            ], 10),
            self::record($messages, 'material', 'comparison', 'materialProof.comparison.title', 'materialProof.comparison.eyebrow', 'materialProof.comparison.description', null, null, [
                'variant' => 'comparison',
                'columns' => self::stringItems($messages, 'materialProof.comparison.columns', 'label'),
                'rows' => self::comparisonRows($messages),
                'disclaimer_translations' => self::translations($messages, 'materialProof.comparison.disclaimer'),
            ], 11),
            self::record($messages, 'material', 'credibility', 'home.credibility.title', 'home.credibility.eyebrow', null, null, null, [
                'variant' => 'credibility',
                'items' => self::cardItems($messages, 'home.credibility.features', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'metrics' => self::stringItems($messages, 'home.credibility.benefits', 'description'),
            ], 12, '/images/material-texture.jpg'),
            self::record($messages, 'material', 'trust_and_credibility', 'trustAndCredibility.title', 'trustAndCredibility.eyebrow', 'trustAndCredibility.description', null, null, [
                'variant' => 'trust',
                'items' => self::cardItems($messages, 'trustAndCredibility.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'disclaimer_translations' => self::translations($messages, 'trustAndCredibility.disclaimer'),
            ], 13),
            self::record($messages, 'material', 'pilot_projects', 'pilotProjects.title', 'pilotProjects.eyebrow', 'pilotProjects.description', null, null, [
                'variant' => 'pilot_projects',
                'items' => self::cardItems($messages, 'pilotProjects.items', [
                    'title' => 'title',
                    'status' => 'status',
                    'description' => 'description',
                ]),
            ], 14),
            self::record($messages, 'material', 'collaboration', 'home.collaboration.title', 'home.collaboration.eyebrow', null, null, null, [
                'variant' => 'collaboration',
                'items' => self::cardItems($messages, 'home.collaboration.cards', [
                    'title' => 'title',
                    'subtitle' => 'forWhom',
                    'description' => 'description',
                    'cta_label' => 'cta',
                ], [], ['b2b#inquiry', 'b2b?leadType=sample_request#inquiry', 'b2b?leadType=product_development_collaboration#inquiry']),
                'process_title_translations' => self::translations($messages, 'home.collaboration.processTitle'),
                'steps' => self::stringItems($messages, 'home.collaboration.steps', 'label'),
            ], 15),
            self::record($messages, 'material', 'final_cta', 'home.finalCta.title', null, 'home.finalCta.description', null, null, [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::translations($messages, 'home.finalCta.primaryCta'),
                'primary_cta_url' => 'b2b#inquiry',
                'secondary_cta_label_translations' => self::translations($messages, 'home.finalCta.secondaryCta'),
                'secondary_cta_url' => 'store',
            ], 16),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function messages(): array
    {
        $basePaths = [
            base_path('../B2C_frontend/messages'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'B2C_frontend'.DIRECTORY_SEPARATOR.'messages',
        ];
        $messages = [];

        foreach (['en', 'zh', 'ko'] as $locale) {
            foreach ($basePaths as $basePath) {
                $path = $basePath.DIRECTORY_SEPARATOR.$locale.'.json';

                if (! is_file($path)) {
                    continue;
                }

                $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    $messages[$locale] = $decoded;
                    break;
                }
            }
        }

        return count($messages) === 3 ? $messages : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function record(
        array $messages,
        string $pageKey,
        string $key,
        ?string $titlePath,
        ?string $subtitlePath,
        ?string $contentPath,
        ?string $ctaLabelPath,
        ?string $ctaUrl,
        array $payload,
        int $sortOrder,
        ?string $mediaUrl = null,
    ): array {
        if ($mediaUrl !== null && ! array_key_exists('media_url', $payload)) {
            $payload['media_url'] = $mediaUrl;
        }

        $titleTranslations = $titlePath ? self::translations($messages, $titlePath) : [];
        $subtitleTranslations = $subtitlePath ? self::translations($messages, $subtitlePath) : [];
        $contentTranslations = $contentPath ? self::translations($messages, $contentPath) : [];
        $ctaLabelTranslations = $ctaLabelPath ? self::translations($messages, $ctaLabelPath) : [];

        return [
            'page_key' => $pageKey,
            'key' => $key,
            'title' => $titleTranslations['en'] ?? null,
            'title_translations' => $titleTranslations,
            'subtitle' => $subtitleTranslations['en'] ?? null,
            'subtitle_translations' => $subtitleTranslations,
            'content' => $contentTranslations['en'] ?? null,
            'content_translations' => $contentTranslations,
            'cta_label' => $ctaLabelTranslations['en'] ?? null,
            'cta_label_translations' => $ctaLabelTranslations,
            'cta_url' => $ctaUrl,
            'payload' => $payload,
            'sort_order' => $sortOrder,
            'media_url' => null,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<string, string>
     */
    private static function translations(array $messages, string $path): array
    {
        $translations = [];

        foreach ($messages as $locale => $message) {
            $value = self::get($message, $path);

            if (is_string($value) && trim($value) !== '') {
                $translations[$locale] = $value;
            }
        }

        return $translations;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function get(array $source, string $path): mixed
    {
        $value = $source;

        foreach (explode('.', $path) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @param  array<string, string>  $fieldMap
     * @param  array<int, string>  $mediaUrls
     * @param  array<int, string>  $ctaUrls
     * @return array<int, array<string, mixed>>
     */
    private static function cardItems(array $messages, string $path, array $fieldMap, array $mediaUrls = [], array $ctaUrls = []): array
    {
        $source = self::get($messages['en'], $path);

        if (! is_array($source)) {
            return [];
        }

        return array_values(array_map(
            function (mixed $item, int $index) use ($messages, $path, $fieldMap, $mediaUrls, $ctaUrls): array {
                $record = [];

                foreach ($fieldMap as $target => $sourceKey) {
                    if ($target === 'cta_url') {
                        $record['cta_url'] = is_array($item) && is_string($item[$sourceKey] ?? null) ? $item[$sourceKey] : null;
                        continue;
                    }

                    if ($target === 'key') {
                        $record['key'] = is_array($item) && is_string($item[$sourceKey] ?? null) ? $item[$sourceKey] : null;
                        continue;
                    }

                    if ($target === 'status') {
                        $record['status'] = is_array($item) && is_string($item[$sourceKey] ?? null) ? $item[$sourceKey] : null;
                        $record['status_translations'] = self::translations($messages, "{$path}.{$index}.{$sourceKey}");
                        continue;
                    }

                    $record[$target.'_translations'] = self::translations($messages, "{$path}.{$index}.{$sourceKey}");
                }

                if (isset($mediaUrls[$index])) {
                    $record['media_url'] = $mediaUrls[$index];
                }

                if (isset($ctaUrls[$index])) {
                    $record['cta_url'] = $ctaUrls[$index];
                }

                return $record;
            },
            $source,
            array_keys($source)
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function stringItems(array $messages, string $path, string $field): array
    {
        $source = self::get($messages['en'], $path);

        if (! is_array($source)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $value, int $index): array => [
                $field.'_translations' => self::translations($messages, "{$path}.{$index}"),
            ],
            $source,
            array_keys($source)
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function comparisonRows(array $messages): array
    {
        return self::cardItems($messages, 'materialProof.comparison.rows', [
            'label' => 'label',
            'oxp' => 'oxp',
            'plastic' => 'plastic',
            'ceramic' => 'ceramic',
            'paper' => 'paper',
        ]);
    }
}
