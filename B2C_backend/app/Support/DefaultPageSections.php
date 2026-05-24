<?php

namespace App\Support;

use App\Enums\PublishStatus;
use App\Models\HomeSection;

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
            ...self::storeRecords($messages),
            ...self::communityRecords($messages),
            ...self::contactRecords($messages),
            ...self::b2bRecords($messages),
            ...self::articlesRecords($messages),
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

    public static function backfill(): void
    {
        foreach (self::records() as $record) {
            self::backfillRecord($record);
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function backfillRecord(array $record): void
    {
        $attributes = self::modelAttributes($record);

        /** @var HomeSection|null $section */
        $section = HomeSection::query()
            ->where('page_key', $record['page_key'])
            ->where('key', $record['key'])
            ->first();

        if (! $section) {
            HomeSection::query()->create($attributes);

            return;
        }

        if (! $section->is_seeded) {
            return;
        }

        $updates = [];

        foreach ([
            'title',
            'subtitle',
            'content',
            'cta_label',
            'cta_url',
            'media_url',
            'status',
            'published_at',
        ] as $field) {
            if (self::isMissingValue($section->{$field} ?? null) && ! self::isMissingValue($attributes[$field] ?? null)) {
                $updates[$field] = $attributes[$field];
            }
        }

        foreach ([
            'title_translations',
            'subtitle_translations',
            'content_translations',
            'cta_label_translations',
        ] as $field) {
            $merged = self::mergeMissingValues($section->{$field} ?? null, $attributes[$field] ?? null);

            if ($merged !== ($section->{$field} ?? null)) {
                $updates[$field] = $merged;
            }
        }

        $mergedPayload = self::mergeMissingValues($section->payload ?? null, $attributes['payload']);

        if ($mergedPayload !== ($section->payload ?? null)) {
            $updates['payload'] = $mergedPayload;
        }

        if (($section->sort_order ?? null) === null) {
            $updates['sort_order'] = $attributes['sort_order'];
        }

        if ($updates === []) {
            return;
        }

        $section->fill($updates);
        $section->save();
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private static function modelAttributes(array $record): array
    {
        return [
            'page_key' => $record['page_key'],
            'key' => $record['key'],
            'title' => $record['title'],
            'title_translations' => $record['title_translations'],
            'subtitle' => $record['subtitle'],
            'subtitle_translations' => $record['subtitle_translations'],
            'content' => $record['content'],
            'content_translations' => $record['content_translations'],
            'cta_label' => $record['cta_label'],
            'cta_label_translations' => $record['cta_label_translations'],
            'cta_url' => $record['cta_url'],
            'payload' => $record['payload'],
            'is_seeded' => true,
            'status' => PublishStatus::Published->value,
            'sort_order' => $record['sort_order'],
            'media_path' => null,
            'media_url' => $record['media_url'] ?? null,
            'published_at' => now(),
        ];
    }

    private static function mergeMissingValues(mixed $current, mixed $default): mixed
    {
        if (self::isMissingValue($current)) {
            return $default;
        }

        if (! is_array($current) || ! is_array($default)) {
            return $current;
        }

        if (array_is_list($current) || array_is_list($default)) {
            return self::mergeMissingListValues($current, $default);
        }

        $merged = $current;

        foreach ($default as $key => $defaultValue) {
            $merged[$key] = array_key_exists($key, $merged)
                ? self::mergeMissingValues($merged[$key], $defaultValue)
                : $defaultValue;
        }

        return $merged;
    }

    /**
     * @param  array<int, mixed>  $current
     * @param  array<int, mixed>  $default
     * @return array<int, mixed>
     */
    private static function mergeMissingListValues(array $current, array $default): array
    {
        $merged = $current;

        foreach ($default as $index => $defaultValue) {
            $merged[$index] = array_key_exists($index, $merged)
                ? self::mergeMissingValues($merged[$index], $defaultValue)
                : $defaultValue;
        }

        ksort($merged);

        return array_values($merged);
    }

    private static function isMissingValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return is_array($value) && $value === [];
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
            self::footerRecord($messages),
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
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function storeRecords(array $messages): array
    {
        return [
            self::record($messages, 'store', 'intro', 'storePage.intro.title', 'storePage.intro.eyebrow', 'storePage.intro.description', 'storePage.intro.primaryCta', 'store#catalogue', [
                'variant' => 'intro',
                'secondary_cta_label_translations' => self::translations($messages, 'storePage.intro.secondaryCta'),
                'secondary_cta_url' => 'material',
            ], 1),
            self::record($messages, 'store', 'product_grid', 'storePage.grid.title', 'storePage.grid.eyebrow', 'storePage.grid.description', null, null, [
                'variant' => 'product_grid',
                ...self::storeGridLabels($messages),
            ], 2),
            self::manualRecord('store', 'applications', self::literalTranslations(
                'Built for tableware, hospitality, homeware, and premium collaborations.',
                '面向餐具、酒店餐饮、家居用品与高端联名合作而构建。',
                '테이블웨어, 호스피탈리티, 홈웨어, 프리미엄 협업을 위해 설계되었습니다.'
            ), self::literalTranslations(
                'Applications',
                '应用场景',
                '적용 분야'
            ), [], [], null, [
                'variant' => 'applications',
                'items' => self::manualCardItems([
                    [
                        'title' => self::literalTranslations('Premium Tableware', '高端餐具', '프리미엄 테이블웨어'),
                        'description' => self::literalTranslations(
                            'Dining pieces with calm tactility and lighter handling.',
                            '触感沉静、拿取更轻盈的餐饮器物。',
                            '차분한 촉감과 더 가벼운 사용감을 지닌 다이닝 제품입니다.'
                        ),
                    ],
                    [
                        'title' => self::literalTranslations('Hospitality Programs', '酒店餐饮项目', '호스피탈리티 프로그램'),
                        'description' => self::literalTranslations(
                            'Tabletop and service objects for boutique hotels and chef-led spaces.',
                            '适用于精品酒店与主厨主导空间的桌面和服务物件。',
                            '부티크 호텔과 셰프 중심 공간을 위한 테이블 및 서비스 오브제입니다.'
                        ),
                    ],
                    [
                        'title' => self::literalTranslations('Homeware Objects', '家居物件', '홈웨어 오브제'),
                        'description' => self::literalTranslations(
                            'Trays, accents, and display pieces that carry the shell story into the home.',
                            '将贝壳故事带入居家的托盘、摆件与展示物件。',
                            '패각의 이야기를 집 안으로 가져오는 트레이, 포인트 오브제, 디스플레이 제품입니다.'
                        ),
                    ],
                    [
                        'title' => self::literalTranslations('Brand Collaborations', '品牌合作', '브랜드 협업'),
                        'description' => self::literalTranslations(
                            'Co-developed launches, gifting lines, and limited premium editions.',
                            '共同开发的新品发布、礼赠系列与限量高端版本。',
                            '공동 개발 출시, 기프트 라인, 한정 프리미엄 에디션에 적합합니다.'
                        ),
                    ],
                ]),
            ], 3),
            self::manualRecord('store', 'credibility', self::literalTranslations(
                'Prepared for certification conversations, traceability, and technical support.',
                '为认证沟通、可追溯性和技术支持做好准备。',
                '인증 논의, 추적성, 기술 지원을 위해 준비되어 있습니다.'
            ), self::literalTranslations(
                'Credibility',
                '可信度',
                '신뢰성'
            ), [], [], null, [
                'variant' => 'credibility',
                'benefits' => self::manualStringItems([
                    self::literalTranslations('Material story focused only on reclaimed oyster shell content', '材料叙事聚焦于回收牡蛎壳内容。', '소재 스토리는 회수된 굴 패각 함량에 집중합니다.'),
                    self::literalTranslations('Traceability notes designed for sourcing and brand reviews', '可追溯说明面向采购与品牌审核而设计。', '소싱 및 브랜드 검토를 위한 추적성 메모를 제공합니다.'),
                    self::literalTranslations('Technical support for review packs, moulding direction, and application fit', '为评估材料、模具方向与应用适配提供技术支持。', '검토 패키지, 성형 방향, 적용 적합성에 대한 기술 지원을 제공합니다.'),
                    self::literalTranslations('Placeholder certification and food-contact modules ready for backend integration', '预留认证与食品接触模块，便于后续接入后端。', '인증 및 식품 접촉 모듈은 향후 백엔드 연동을 위해 준비되어 있습니다.'),
                ], 'description'),
                'metrics' => self::manualStringItems([
                    self::literalTranslations('Material story focused only on reclaimed oyster shell content', '材料叙事聚焦于回收牡蛎壳内容。', '소재 스토리는 회수된 굴 패각 함량에 집중합니다.'),
                    self::literalTranslations('Traceability notes designed for sourcing and brand reviews', '可追溯说明面向采购与品牌审核而设计。', '소싱 및 브랜드 검토를 위한 추적성 메모를 제공합니다.'),
                    self::literalTranslations('Technical support for review packs, moulding direction, and application fit', '为评估材料、模具方向与应用适配提供技术支持。', '검토 패키지, 성형 방향, 적용 적합성에 대한 기술 지원을 제공합니다.'),
                    self::literalTranslations('Placeholder certification and food-contact modules ready for backend integration', '预留认证与食品接触模块，便于后续接入后端。', '인증 및 식품 접촉 모듈은 향후 백엔드 연동을 위해 준비되어 있습니다.'),
                ], 'description'),
                'items' => self::manualCardItems([
                    [
                        'title' => self::literalTranslations('Certification Readiness', '认证准备', '인증 준비성'),
                        'description' => self::literalTranslations(
                            'Frontend placeholders are prepared for third-party testing documents and future compliance records.',
                            '前端已预留第三方测试文件与未来合规记录的位置。',
                            '제3자 시험 문서와 향후 컴플라이언스 기록을 위한 프런트엔드 영역이 준비되어 있습니다.'
                        ),
                    ],
                    [
                        'title' => self::literalTranslations('Technical Support', '技术支持', '기술 지원'),
                        'description' => self::literalTranslations(
                            'The site structure now includes space for project-specific material guidance and application reviews.',
                            '站点结构已包含项目专属材料指导与应用评审空间。',
                            '사이트 구조에는 프로젝트별 소재 가이드와 적용 검토를 위한 공간이 포함되어 있습니다.'
                        ),
                    ],
                    [
                        'title' => self::literalTranslations('Traceable Origin', '可追溯来源', '추적 가능한 원산지'),
                        'description' => self::literalTranslations(
                            'Source storytelling remains focused on South Korean oyster shell recovery and controlled pellet making.',
                            '来源叙事持续聚焦韩国牡蛎壳回收与受控颗粒制造。',
                            '원산지 스토리는 한국 굴 패각 회수와 관리된 펠릿 제조에 집중합니다.'
                        ),
                    ],
                    [
                        'title' => self::literalTranslations('Material Request Workflow', '材料申请流程', '소재 요청 워크플로'),
                        'description' => self::literalTranslations(
                            'Teams can move from inquiry to material review planning without leaving the site architecture.',
                            '团队可在站内从询盘推进到材料评估规划。',
                            '팀은 사이트 구조 안에서 문의부터 소재 검토 계획까지 이어갈 수 있습니다.'
                        ),
                    ],
                ]),
            ], 4, '/images/material-texture.jpg'),
            self::record($messages, 'store', 'store_faq', 'storePage.faq.title', 'storePage.faq.eyebrow', null, null, null, [
                'variant' => 'store_faq',
                'items' => self::faqItems($messages, 'storePage.faq.items'),
            ], 5),
            self::manualRecord('store', 'final_cta', self::literalTranslations(
                'Bring OXP into your next tableware, pellet, or concept program.',
                '将 OXP 带入你的下一个餐具、颗粒或概念项目。',
                '다음 테이블웨어, 펠릿 또는 컨셉 프로그램에 OXP를 더하세요.'
            ), [], self::literalTranslations(
                'Use the store for product inspiration, or contact the team for B2B supply and development.',
                '通过商店寻找产品灵感，或联系团队开展 B2B 供应与开发。',
                '스토어에서 제품 영감을 얻거나 B2B 공급 및 개발을 위해 팀에 문의하세요.'
            ), self::literalTranslations(
                'Send a B2B Inquiry',
                '发送 B2B 询盘',
                'B2B 문의 보내기'
            ), 'b2b', [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::literalTranslations('Send a B2B Inquiry', '发送 B2B 询盘', 'B2B 문의 보내기'),
                'primary_cta_url' => 'b2b',
                'secondary_cta_label_translations' => self::literalTranslations('Browse the Store', '浏览商店', '스토어 둘러보기'),
                'secondary_cta_url' => 'store',
            ], 6),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function communityRecords(array $messages): array
    {
        return [
            self::record($messages, 'community', 'intro', 'communityPage.intro.title', 'communityPage.intro.eyebrow', 'communityPage.intro.description', 'communityPage.intro.primaryCta', 'community/new', [
                'variant' => 'intro',
                'secondary_cta_label_translations' => self::translations($messages, 'communityPage.intro.secondaryCta'),
                'secondary_cta_url' => 'community',
            ], 1),
            self::record($messages, 'community', 'open_concepts', 'communityPage.ideas.title', 'communityPage.ideas.eyebrow', 'communityPage.ideas.description', null, null, [
                'variant' => 'open_concepts',
                ...self::localizedPayloadField('focus_label', self::translations($messages, 'communityPage.ideas.focusLabel')),
                ...self::localizedPayloadField('stage_label', self::translations($messages, 'communityPage.ideas.stageLabel')),
                ...self::localizedPayloadField('support_label', self::translations($messages, 'communityPage.ideas.supportLabel')),
                ...self::localizedPayloadField('cta_primary_label', self::translations($messages, 'communityPage.ideas.ctaPrimary')),
                'cta_primary_url' => 'community/new',
                ...self::localizedPayloadField('cta_secondary_label', self::translations($messages, 'communityPage.ideas.ctaSecondary')),
                'cta_secondary_url' => 'contact',
            ], 2),
            self::manualRecord('community', 'final_cta', self::literalTranslations(
                'Share a concept or support the next OXP idea.',
                '分享一个概念，或支持下一个 OXP 想法。',
                '컨셉을 공유하거나 다음 OXP 아이디어를 지원하세요.'
            ), [], self::literalTranslations(
                'Join the community to post ideas, find collaborators, and connect product concepts to future support links.',
                '加入社区，发布想法、寻找协作者，并将产品概念连接到未来支持链接。',
                '커뮤니티에 참여해 아이디어를 올리고 협업자를 찾으며 제품 컨셉을 향후 지원 링크와 연결하세요.'
            ), self::literalTranslations(
                'Submit a Concept',
                '提交概念',
                '컨셉 제출'
            ), 'community/new', [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::literalTranslations('Submit a Concept', '提交概念', '컨셉 제출'),
                'primary_cta_url' => 'community/new',
                'secondary_cta_label_translations' => self::literalTranslations('Browse Concepts', '浏览概念', '컨셉 둘러보기'),
                'secondary_cta_url' => 'community',
            ], 3),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function contactRecords(array $messages): array
    {
        return [
            self::record($messages, 'contact', 'intro', 'contactPage.intro.title', 'contactPage.intro.eyebrow', 'contactPage.intro.description', 'contactPage.intro.primaryCta', 'contact#inquiry', [
                'variant' => 'intro',
                'secondary_cta_label_translations' => self::translations($messages, 'contactPage.intro.secondaryCta'),
                'secondary_cta_url' => '#inquiry',
                'anchor_id' => 'contact-intro',
            ], 1),
            self::record($messages, 'contact', 'details', 'contactPage.details.title', 'contactPage.details.eyebrow', 'contactPage.details.description', null, null, [
                'variant' => 'contact_details',
                'cards' => self::contactDetailCards($messages),
                'items' => self::contactDetailCards($messages),
                'response_translations' => self::translations($messages, 'contactPage.details.response'),
            ], 2),
            self::manualRecord('contact', 'inquiry_form', self::literalTranslations(
                'Send a structured inquiry',
                '发送结构化询盘',
                '구조화된 문의 보내기'
            ), self::literalTranslations(
                'Project Brief',
                '项目简报',
                '프로젝트 브리프'
            ), self::literalTranslations(
                'Tell us about your product, material, retail, hospitality, or collaboration request. The team will review the details and respond by email.',
                '请告诉我们你的产品、材料、零售、酒店餐饮或合作需求。团队会审核详情并通过邮件回复。',
                '제품, 소재, 리테일, 호스피탈리티 또는 협업 요청을 알려주세요. 팀이 내용을 검토한 뒤 이메일로 답변합니다.'
            ), self::literalTranslations(
                'Submit Inquiry',
                '提交询盘',
                '문의 제출'
            ), null, [
                ...self::leadFormPayload($messages),
                'form_anchor_id' => 'inquiry',
                'submit_success_message_translations' => self::translations($messages, 'common.success.inquirySubmitted'),
                'submit_button_label_translations' => self::literalTranslations('Submit Inquiry', '提交询盘', '문의 제출'),
                'privacy_note_translations' => self::literalTranslations(
                    'Your details are used only to respond to this inquiry and related project communication.',
                    '你的信息仅用于回复此询盘及相关项目沟通。',
                    '입력하신 정보는 이 문의와 관련 프로젝트 커뮤니케이션에 응답하는 데에만 사용됩니다.'
                ),
                'topic_options' => self::manualStringItems([
                    self::literalTranslations('Material supply', '材料供应', '소재 공급'),
                    self::literalTranslations('Material request', '材料申请', '소재 요청'),
                    self::literalTranslations('Product development', '产品开发', '제품 개발'),
                    self::literalTranslations('Retail or store inquiry', '零售或商店咨询', '리테일 또는 스토어 문의'),
                    self::literalTranslations('Hospitality project', '酒店餐饮项目', '호스피탈리티 프로젝트'),
                    self::literalTranslations('Community collaboration', '社区合作', '커뮤니티 협업'),
                    self::literalTranslations('General question', '一般问题', '일반 질문'),
                ], 'label'),
            ], 3),
            self::manualRecord('contact', 'final_cta', self::literalTranslations(
                'Start with a short message. We will guide the next step.',
                '从一段简短留言开始。我们会引导下一步。',
                '짧은 메시지로 시작하세요. 다음 단계를 안내해 드리겠습니다.'
            ), [], self::literalTranslations(
                'Whether you are exploring pellets, finished products, hospitality use, or community concepts, the OXP team can route your inquiry to the right workflow.',
                '无论你正在了解颗粒、成品、酒店餐饮应用或社区概念，OXP 团队都可以将你的询盘转入合适流程。',
                '펠릿, 완제품, 호스피탈리티 활용 또는 커뮤니티 콘셉트를 검토 중이라면 OXP 팀이 문의를 알맞은 워크플로로 연결합니다.'
            ), self::literalTranslations(
                'Email the Team',
                '给团队发邮件',
                '팀에 이메일 보내기'
            ), 'contact#inquiry', [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::literalTranslations('Email the Team', '给团队发邮件', '팀에 이메일 보내기'),
                'primary_cta_url' => 'contact#inquiry',
                'secondary_cta_label_translations' => self::literalTranslations('Request Review Pack', '申请评估材料', '검토 패키지 요청'),
                'secondary_cta_url' => 'b2b',
                'anchor_id' => 'contact-final-cta',
            ], 4),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function b2bRecords(array $messages): array
    {
        return [
            self::record($messages, 'b2b', 'intro', 'b2bPage.intro.title', 'b2bPage.intro.eyebrow', 'b2bPage.intro.description', 'b2bPage.intro.primaryCta', 'b2b?leadType=inquiry#inquiry', [
                'variant' => 'intro',
                'secondary_cta_label_translations' => self::translations($messages, 'b2bPage.intro.secondaryCta'),
                'secondary_cta_url' => 'material',
            ], 1),
            self::record($messages, 'b2b', 'collaboration', 'home.collaboration.title', 'home.collaboration.eyebrow', null, null, null, [
                'variant' => 'collaboration',
                'items' => self::cardItems($messages, 'home.collaboration.cards', [
                    'title' => 'title',
                    'subtitle' => 'forWhom',
                    'description' => 'description',
                    'cta_label' => 'cta',
                ], [], ['b2b#inquiry', 'b2b?leadType=sample_request#inquiry', 'b2b?leadType=product_development_collaboration#inquiry']),
                'process_title_translations' => self::translations($messages, 'home.collaboration.processTitle'),
                'steps' => self::stringItems($messages, 'home.collaboration.steps', 'label'),
            ], 2),
            self::record($messages, 'b2b', 'process', 'b2bPage.process.title', 'b2bPage.process.eyebrow', null, null, null, [
                'variant' => 'process',
                'items' => self::cardItems($messages, 'b2bPage.process.steps', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
            ], 3),
            self::record($messages, 'b2b', 'cta_strip', null, null, null, null, null, [
                'variant' => 'cta_strip',
                'sample_translations' => self::translations($messages, 'b2bPage.ctaStrip.sample'),
                'material_data_translations' => self::translations($messages, 'b2bPage.ctaStrip.materialData'),
                'requirements_translations' => self::translations($messages, 'b2bPage.ctaStrip.requirements'),
                'bulk_supply_translations' => self::translations($messages, 'b2bPage.ctaStrip.bulkSupply'),
            ], 4),
            self::record($messages, 'b2b', 'applications', 'b2bPage.applications.title', 'b2bPage.applications.eyebrow', null, null, null, [
                'variant' => 'applications',
                'items' => self::cardItems($messages, 'b2bPage.applications.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
            ], 5),
            self::record($messages, 'b2b', 'material_facts', 'home.materialFacts.title', 'home.materialFacts.eyebrow', 'home.materialFacts.sheetDescription', 'home.materialFacts.sheetCta', 'b2b?leadType=sample_request#inquiry', [
                'variant' => 'material_facts',
                'sheet_title_translations' => self::translations($messages, 'home.materialFacts.sheetTitle'),
                'note_translations' => self::translations($messages, 'home.materialFacts.note'),
                'metrics' => self::cardItems($messages, 'home.materialFacts.infoCards', [
                    'label' => 'label',
                    'value' => 'value',
                ]),
            ], 6),
            self::record($messages, 'b2b', 'credibility', 'home.credibility.title', 'home.credibility.eyebrow', null, null, null, [
                'variant' => 'credibility',
                'items' => self::cardItems($messages, 'home.credibility.features', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'metrics' => self::stringItems($messages, 'home.credibility.benefits', 'description'),
            ], 7, '/images/material-texture.jpg'),
            self::record($messages, 'b2b', 'trust_and_credibility', 'trustAndCredibility.title', 'trustAndCredibility.eyebrow', 'trustAndCredibility.description', null, null, [
                'variant' => 'trust',
                'items' => self::cardItems($messages, 'trustAndCredibility.cards', [
                    'title' => 'title',
                    'description' => 'description',
                ]),
                'disclaimer_translations' => self::translations($messages, 'trustAndCredibility.disclaimer'),
            ], 8),
            self::record($messages, 'b2b', 'pilot_projects', 'pilotProjects.title', 'pilotProjects.eyebrow', 'pilotProjects.description', null, null, [
                'variant' => 'pilot_projects',
                'items' => self::cardItems($messages, 'pilotProjects.items', [
                    'title' => 'title',
                    'status' => 'status',
                    'description' => 'description',
                ]),
            ], 9),
            self::record($messages, 'b2b', 'form', 'b2bPage.form.title', 'b2bPage.form.eyebrow', 'b2bPage.form.description', 'b2bPage.form.submit', null, [
                ...self::leadFormPayload($messages),
            ], 10),
            self::record($messages, 'b2b', 'after_submit', 'b2bPage.afterSubmit.title', 'b2bPage.afterSubmit.eyebrow', null, null, null, [
                'variant' => 'after_submit',
                'items' => self::stringItems($messages, 'b2bPage.afterSubmit.items', 'label'),
            ], 11),
            self::record($messages, 'b2b', 'final_cta', 'home.finalCta.title', null, 'home.finalCta.description', null, null, [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::translations($messages, 'home.finalCta.primaryCta'),
                'primary_cta_url' => 'b2b#inquiry',
                'secondary_cta_label_translations' => self::translations($messages, 'home.finalCta.secondaryCta'),
                'secondary_cta_url' => 'store',
            ], 12),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function articlesRecords(array $messages): array
    {
        return [
            self::record($messages, 'articles', 'intro', 'articleFeed.defaultTitle', 'articleFeed.defaultEyebrow', 'articleFeed.defaultDescription', 'articleFeed.defaultCta', 'articles#articles', [
                'variant' => 'intro',
                'secondary_cta_label_translations' => self::translations($messages, 'header.contact'),
                'secondary_cta_url' => 'contact',
            ], 1),
            self::record($messages, 'articles', 'article_feed', 'articleFeed.defaultTitle', 'articleFeed.defaultEyebrow', 'articleFeed.defaultDescription', 'articleFeed.defaultCta', 'articles', [
                'variant' => 'article_feed',
                'limit' => 12,
                ...self::localizedPayloadField('empty_title', self::translations($messages, 'articleFeed.emptyTitle')),
                ...self::localizedPayloadField('empty_description', self::translations($messages, 'articleFeed.emptyDescription')),
            ], 2),
            self::record($messages, 'articles', 'final_cta', 'home.finalCta.title', null, 'home.finalCta.description', null, null, [
                'variant' => 'final_cta',
                'primary_cta_label_translations' => self::translations($messages, 'home.finalCta.primaryCta'),
                'primary_cta_url' => 'b2b#inquiry',
                'secondary_cta_label_translations' => self::translations($messages, 'home.finalCta.secondaryCta'),
                'secondary_cta_url' => 'store',
            ], 3),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    private static function footerRecord(array $messages): array
    {
        $record = self::record($messages, 'home', 'footer', null, null, 'footer.description', null, null, [
            'variant' => 'footer',
            'home_translations' => self::translations($messages, 'header.home'),
            'material_translations' => self::translations($messages, 'header.material'),
            'store_translations' => self::translations($messages, 'header.store'),
            'b2b_translations' => self::translations($messages, 'header.b2b'),
            'community_translations' => self::translations($messages, 'header.community'),
            'contact_translations' => self::translations($messages, 'header.contact'),
            'explore_translations' => self::translations($messages, 'footer.explore'),
            'business_translations' => self::translations($messages, 'footer.business'),
            'community_label_translations' => self::translations($messages, 'footer.communityLabel'),
            'material_sheet_translations' => self::translations($messages, 'footer.materialSheet'),
            'sample_request_translations' => self::translations($messages, 'footer.sampleRequest'),
            'product_development_translations' => self::translations($messages, 'footer.productDevelopment'),
            'idea_support_translations' => self::translations($messages, 'footer.ideaSupport'),
            'concept_fund_translations' => self::translations($messages, 'footer.conceptFund'),
            'email_label_translations' => self::translations($messages, 'footer.emailLabel'),
            'phone_label_translations' => self::translations($messages, 'footer.phoneLabel'),
            'location_label_translations' => self::translations($messages, 'footer.locationLabel'),
            'location_value_translations' => self::translations($messages, 'footer.locationValue'),
            'copyright_translations' => self::translations($messages, 'footer.copyright'),
            'privacy_translations' => self::translations($messages, 'footer.privacy'),
            'terms_translations' => self::translations($messages, 'footer.terms'),
            'email_value' => 'Contact us',
            'email_href' => 'contact#contact-form',
            'phone_value' => '+82 51-555-0188',
            'phone_href' => 'tel:+82515550188',
            'location_href' => 'contact',
            'privacy_href' => 'privacy',
            'terms_href' => 'terms',
            'social_links' => [],
            'legal_links' => [
                [
                    'label_translations' => self::translations($messages, 'footer.privacy'),
                    'href' => 'privacy',
                ],
                [
                    'label_translations' => self::translations($messages, 'footer.terms'),
                    'href' => 'terms',
                ],
            ],
        ], 99);

        $record['title'] = 'Footer';
        $record['title_translations'] = ['en' => 'Footer'];
        $record['subtitle'] = 'Site footer';
        $record['subtitle_translations'] = ['en' => 'Site footer'];

        return $record;
    }

    /**
     * @param  array<string, string>  $titleTranslations
     * @param  array<string, string>  $subtitleTranslations
     * @param  array<string, string>  $contentTranslations
     * @param  array<string, string>  $ctaLabelTranslations
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function manualRecord(
        string $pageKey,
        string $key,
        array $titleTranslations,
        array $subtitleTranslations,
        array $contentTranslations,
        array $ctaLabelTranslations,
        ?string $ctaUrl,
        array $payload,
        int $sortOrder,
        ?string $mediaUrl = null,
    ): array {
        if ($mediaUrl !== null && ! array_key_exists('media_url', $payload)) {
            $payload['media_url'] = $mediaUrl;
        }

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
            'media_url' => $mediaUrl,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function literalTranslations(string $en, string $zh, string $ko): array
    {
        return [
            'en' => $en,
            'zh' => $zh,
            'ko' => $ko,
        ];
    }

    /**
     * @param  array<string, string>  $translations
     * @return array<string, mixed>
     */
    private static function localizedPayloadField(string $key, array $translations): array
    {
        return [
            $key => $translations['en'] ?? null,
            $key.'_translations' => $translations,
        ];
    }

    /**
     * @param  array<int, array{title?: array<string, string>, description?: array<string, string>}>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function manualCardItems(array $items): array
    {
        return array_map(
            fn (array $item): array => [
                'title' => $item['title']['en'] ?? null,
                'title_translations' => $item['title'] ?? [],
                'description' => $item['description']['en'] ?? null,
                'description_translations' => $item['description'] ?? [],
            ],
            $items
        );
    }

    /**
     * @param  array<int, array<string, string>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function manualStringItems(array $items, string $field): array
    {
        return array_map(
            fn (array $translations): array => [
                $field => $translations['en'] ?? null,
                $field.'_translations' => $translations,
            ],
            $items
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    private static function leadFormPayload(array $messages): array
    {
        return [
            'variant' => 'lead_form',
            'groups' => self::translatedMap($messages, 'b2bPage.form.groups', [
                'contact' => 'contact',
                'project' => 'project',
                'material' => 'material',
            ]),
            'fields' => self::translatedMap($messages, 'b2bPage.form.fields', self::leadFormFieldMap()),
            'placeholders' => self::translatedMap($messages, 'b2bPage.form.placeholders', self::leadFormFieldMap()),
            'validation' => self::translatedMap($messages, 'b2bPage.form.validation', self::leadFormValidationMap()),
            'interest_options' => self::leadInterestOptions($messages),
            'panel_copy' => self::leadPanelCopy($messages),
            'product_context_label_translations' => self::translations($messages, 'b2bPage.form.productContextLabel'),
            'submit_button_label_translations' => self::translations($messages, 'b2bPage.form.submit'),
            'submit_success_message_translations' => self::translations($messages, 'b2bPage.form.success'),
            'privacy_note_translations' => self::translations($messages, 'b2bPage.form.disclaimer'),
            'disclaimer_translations' => self::translations($messages, 'b2bPage.form.disclaimer'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function leadFormFieldMap(): array
    {
        return [
            'name' => 'name',
            'company' => 'company',
            'organization_type' => 'organizationType',
            'email' => 'email',
            'phone' => 'phone',
            'country' => 'country',
            'region' => 'region',
            'company_website' => 'companyWebsite',
            'job_title' => 'jobTitle',
            'application' => 'application',
            'volume' => 'volume',
            'timeline' => 'timeline',
            'material_interest' => 'materialInterest',
            'quantity_estimate' => 'quantityEstimate',
            'shipping_country' => 'shippingCountry',
            'shipping_region' => 'shippingRegion',
            'shipping_address' => 'shippingAddress',
            'intended_use' => 'intendedUse',
            'collaboration_goal' => 'collaborationGoal',
            'project_stage' => 'projectStage',
            'message' => 'message',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function leadFormValidationMap(): array
    {
        return [
            'default_field' => 'defaultField',
            'max' => 'max',
            'name_required' => 'nameRequired',
            'company_required' => 'companyRequired',
            'email_required' => 'emailRequired',
            'email_invalid' => 'emailInvalid',
            'url_invalid' => 'urlInvalid',
            'message_required' => 'messageRequired',
            'application_required' => 'applicationRequired',
            'organization_type_required' => 'organizationTypeRequired',
            'collaboration_goal_required' => 'collaborationGoalRequired',
            'material_interest_required' => 'materialInterestRequired',
            'intended_use_required' => 'intendedUseRequired',
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @param  array<string, string>  $fields
     * @return array<string, mixed>
     */
    private static function translatedMap(array $messages, string $basePath, array $fields): array
    {
        $payload = [];

        foreach ($fields as $payloadKey => $messageKey) {
            $payload[$payloadKey.'_translations'] = self::translations($messages, "{$basePath}.{$messageKey}");
        }

        return $payload;
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function leadInterestOptions(array $messages): array
    {
        $options = [
            ['id' => 'sample_request', 'interest_type' => 'sample_request'],
            ['id' => 'inquiry', 'interest_type' => 'pellet_supply'],
            ['id' => 'product_development_collaboration', 'interest_type' => 'product_development'],
            ['id' => 'bulk_order', 'interest_type' => 'bulk_order'],
            ['id' => 'partnership_inquiry', 'interest_type' => 'partnership'],
            ['id' => 'other', 'interest_type' => 'other'],
        ];

        return array_map(
            fn (array $option): array => [
                ...$option,
                'label_translations' => self::translations($messages, "b2bPage.form.interestOptions.{$option['interest_type']}.label"),
                'description_translations' => self::translations($messages, "b2bPage.form.interestOptions.{$option['interest_type']}.description"),
            ],
            $options
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function leadPanelCopy(array $messages): array
    {
        $panelCopy = [];

        foreach (['sample_request', 'pellet_supply', 'product_development', 'bulk_order', 'partnership', 'other'] as $interestType) {
            $lines = self::get($messages['en'], "b2bPage.form.panelCopy.{$interestType}");

            if (! is_array($lines)) {
                $panelCopy[$interestType] = [];

                continue;
            }

            $panelCopy[$interestType] = array_map(
                fn (mixed $line, int $index): array => [
                    'line' => is_string($line) ? $line : null,
                    'line_translations' => self::translations($messages, "b2bPage.form.panelCopy.{$interestType}.{$index}"),
                ],
                $lines,
                array_keys($lines)
            );
        }

        return $panelCopy;
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    private static function storeGridLabels(array $messages): array
    {
        $fields = [
            'price_prefix' => 'pricePrefix',
            'availability_label' => 'availabilityLabel',
            'category_quick_filter_label' => 'categoryQuickFilterLabel',
            'filters_title' => 'filtersTitle',
            'search_label' => 'searchLabel',
            'search_placeholder' => 'searchPlaceholder',
            'all_option' => 'allOption',
            'filter_hint' => 'filterHint',
            'category_hint' => 'categoryHint',
            'active_filters_label' => 'activeFiltersLabel',
            'remove_filter_label' => 'removeFilterLabel',
            'sort_label' => 'sortLabel',
            'stock_label' => 'stockLabel',
            'price_label' => 'priceLabel',
            'min_price' => 'minPrice',
            'max_price' => 'maxPrice',
            'apply_filters' => 'applyFilters',
            'clear_all' => 'clearAll',
            'result_label' => 'resultLabel',
            'search_result_title' => 'searchResultTitle',
            'filtered_products_title' => 'filteredProductsTitle',
            'all_products_title' => 'allProductsTitle',
            'showing_label' => 'showingLabel',
            'empty_title' => 'emptyTitle',
            'empty_description' => 'emptyDescription',
            'empty_action' => 'emptyAction',
            'error_title' => 'errorTitle',
            'error_description' => 'errorDescription',
            'retry_action' => 'retryAction',
            'attribute_label' => 'attributeLabel',
        ];
        $payload = [];

        foreach ($fields as $payloadKey => $messageKey) {
            $payload = [
                ...$payload,
                ...self::localizedPayloadField(
                    $payloadKey,
                    self::translations($messages, "storePage.grid.{$messageKey}")
                ),
            ];
        }

        return $payload;
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function faqItems(array $messages, string $path): array
    {
        $source = self::get($messages['en'], $path);

        if (! is_array($source)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $item, int $index): array => [
                'question' => is_array($item) && is_string($item['question'] ?? null) ? $item['question'] : null,
                'question_translations' => self::translations($messages, "{$path}.{$index}.question"),
                'answer' => is_array($item) && is_string($item['answer'] ?? null) ? $item['answer'] : null,
                'answer_translations' => self::translations($messages, "{$path}.{$index}.answer"),
            ],
            $source,
            array_keys($source)
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private static function contactDetailCards(array $messages): array
    {
        $cards = self::cardItems($messages, 'contactPage.details.cards', [
            'label' => 'label',
            'value' => 'value',
            'detail' => 'detail',
        ]);

        $hrefs = [
            ['href_type' => 'email', 'href' => 'contact#inquiry'],
            ['href_type' => 'phone', 'href' => 'tel:+82515550188'],
            ['href_type' => 'text', 'href' => null],
        ];

        return array_map(
            fn (array $card, int $index): array => $card + ($hrefs[$index] ?? ['href_type' => 'text', 'href' => null]),
            $cards,
            array_keys($cards)
        );
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
            'media_url' => $mediaUrl,
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
