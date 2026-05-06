<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\ContentManagementService;
use App\Support\LocalizedContent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $locale = $this->locale($request);
        $material = $contentManagementService->listPublicMaterials(['featured' => true])->first()
            ?? $contentManagementService->listPublicMaterials()->first();

        if ($material instanceof Material) {
            try {
                return $this->successResponse(
                    $this->cmsPayload(
                        $contentManagementService->findPublicMaterial($material->slug),
                        $locale
                    )
                );
            } catch (ModelNotFoundException) {
                // Fall through to the legacy static payload.
            }
        }

        return $this->successResponse($this->payload($locale));
    }

    public function show(
        Request $request,
        string $identifier,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $locale = $this->locale($request);

        try {
            return $this->successResponse(
                $this->cmsPayload(
                    $contentManagementService->findPublicMaterial($identifier),
                    $locale
                )
            );
        } catch (ModelNotFoundException) {
            return $this->successResponse($this->payload($locale));
        }
    }

    private function cmsPayload(Material $material, string $locale): array
    {
        $fallback = $this->payload($locale);
        $title = $this->resolveStringForLocale(
            $material->title_translations,
            $locale,
            $material->title,
            $fallback['name'] ?? null,
        ) ?? $fallback['name'];
        $headline = $this->resolveStringForLocale(
            $material->headline_translations,
            $locale,
            $material->headline,
            $fallback['tagline'] ?? null,
        );
        $summary = $this->resolveStringForLocale(
            $material->summary_translations,
            $locale,
            $material->summary,
            $fallback['origin'] ?? null,
        );

        $processSteps = $material->storySections
            ->values()
            ->map(function ($section, int $index) use ($fallback, $locale): array {
                $fallbackStep = $fallback['process_steps'][$index] ?? [];

                return [
                    'step' => $index + 1,
                    'title' => $this->resolveStringForLocale(
                        $section->title_translations,
                        $locale,
                        $section->title,
                        $fallbackStep['title'] ?? null,
                    ) ?? (string) ($index + 1),
                    'body' => $this->resolveStringForLocale(
                        $section->content_translations,
                        $locale,
                        $section->content,
                        $fallbackStep['body'] ?? null,
                    ) ?? '',
                ];
            })
            ->filter(fn (array $step): bool => trim((string) $step['title']) !== '' || trim((string) $step['body']) !== '')
            ->values()
            ->all();

        $fallbackProperties = collect($fallback['properties'] ?? [])->keyBy('key');
        $properties = $material->specs
            ->values()
            ->map(function ($spec) use ($fallbackProperties, $locale): array {
                $fallbackProperty = $fallbackProperties->get($spec->key ?? '', []);

                return [
                    'key' => $spec->key ?? '',
                    'label' => $this->resolveStringForLocale(
                        $spec->label_translations,
                        $locale,
                        $spec->label,
                        $fallbackProperty['label'] ?? null,
                    ) ?? '',
                    'value' => trim(collect([
                        $this->resolveStringForLocale(
                            $spec->value_translations,
                            $locale,
                            $spec->value,
                            $fallbackProperty['value'] ?? null,
                        ),
                        $spec->unit,
                    ])->filter()->implode(' ')),
                    'vs' => $this->resolveStringForLocale(
                        $spec->detail_translations,
                        $locale,
                        $spec->detail,
                        $fallbackProperty['vs'] ?? null,
                    ) ?? '',
                ];
            })
            ->filter(fn (array $property): bool => trim((string) $property['label']) !== '' || trim((string) $property['value']) !== '')
            ->values()
            ->all();

        $applications = $material->applications
            ->values()
            ->map(fn ($application): array => [
                'id' => $application->id,
                'title' => $this->resolveStringForLocale(
                    $application->title_translations,
                    $locale,
                    $application->title,
                ) ?? '',
                'subtitle' => $this->resolveStringForLocale(
                    $application->subtitle_translations,
                    $locale,
                    $application->subtitle,
                ),
                'description' => $this->resolveStringForLocale(
                    $application->description_translations,
                    $locale,
                    $application->description,
                ) ?? '',
                'audience' => $this->resolveStringForLocale(
                    $application->audience_translations,
                    $locale,
                    $application->audience,
                ),
                'cta_label' => $this->resolveStringForLocale(
                    $application->cta_label_translations,
                    $locale,
                    $application->cta_label,
                ),
                'cta_url' => $application->cta_url,
            ])
            ->filter(fn (array $application): bool => trim((string) $application['title']) !== '' || trim((string) $application['description']) !== '')
            ->values()
            ->all();

        return [
            'name' => $title,
            'tagline' => $headline ?: $title,
            'origin' => $summary ?: ($fallback['origin'] ?? ''),
            'process_steps' => $processSteps !== [] ? $processSteps : $fallback['process_steps'],
            'properties' => $properties !== [] ? $properties : $fallback['properties'],
            'certifications' => $this->localizedCertifications($material->certifications, $locale),
            'technical_downloads' => is_array($material->technical_downloads) ? $material->technical_downloads : [],
            'applications' => $applications,
            'models' => $fallback['models'],
            'colors' => $fallback['colors'],
        ];
    }

    private function resolveStringForLocale(
        mixed $translations,
        string $locale,
        mixed $fallback = null,
        ?string $localeFallback = null,
    ): ?string {
        $fallbackValue = is_string($fallback) || is_numeric($fallback)
            ? trim((string) $fallback)
            : null;
        $normalized = LocalizedContent::normalizeStringTranslations(
            $translations,
            $fallbackValue !== '' ? $fallbackValue : null,
        );

        if ($locale === LocalizedContent::DEFAULT_LOCALE) {
            return $normalized[LocalizedContent::DEFAULT_LOCALE]
                ?? ($fallbackValue !== '' ? $fallbackValue : null)
                ?? $localeFallback;
        }

        return $normalized[$locale] ?? $localeFallback;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function localizedCertifications(mixed $certifications, string $locale): array
    {
        if (! is_array($certifications)) {
            return [];
        }

        return collect($certifications)
            ->filter(fn (mixed $certification): bool => is_array($certification))
            ->map(fn (array $certification): array => $this->localizedCertification($certification, $locale))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $certification
     * @return array<string, mixed>
     */
    private function localizedCertification(array $certification, string $locale): array
    {
        $localized = [
            'key' => $certification['key'] ?? null,
            'status' => $certification['status'] ?? null,
            'verified' => (bool) ($certification['verified'] ?? false),
            'unit' => $certification['unit'] ?? null,
            'tested_at' => $certification['tested_at'] ?? null,
            'document_url' => $certification['document_url'] ?? null,
        ];

        foreach (['label', 'name', 'value', 'result', 'description', 'issuer'] as $field) {
            $value = $this->resolveStringForLocale(
                $certification[$field.'_translations'] ?? null,
                $locale,
                $certification[$field] ?? null,
            );

            if ($value !== null && trim($value) !== '') {
                $localized[$field] = $value;
            }
        }

        return array_filter(
            $localized,
            fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }

    private function locale(Request $request): string
    {
        $locale = strtolower((string) $request->query('locale', 'en'));

        return in_array($locale, ['en', 'zh', 'ko'], true) ? $locale : 'en';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $locale): array
    {
        $payload = $this->fallbackPayloads()[$locale] ?? $this->fallbackPayloads()['en'];

        $payload['certifications'] = [];
        $payload['technical_downloads'] = [];

        return $payload;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fallbackPayloads(): array
    {
        return [
            'en' => [
                'name' => 'Premium Oyster Shell Composite',
                'tagline' => 'A premium, science-backed material platform built from recovered oyster shell.',
                'origin' => 'Designed for premium interior objects, hospitality programs, tableware concepts, and future collaborative product development.',
                'process_steps' => [
                    [
                        'step' => 1,
                        'title' => 'Shell collection',
                        'body' => 'Recovered oyster shells are collected from coastal and seafood-related waste streams, then sorted for material processing.',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Cleaning and thermal purification',
                        'body' => 'Shells are washed, dried, and thermally treated to reduce organic residue and prepare a stable mineral base.',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Pellet compounding',
                        'body' => 'Purified shell powder is blended with a process-dependent binder system and formed into consistent pellets for downstream moulding.',
                    ],
                    [
                        'step' => 4,
                        'title' => 'Compression moulding and finishing',
                        'body' => 'Pellets are compression-moulded into prototype or production-ready objects, then finished for premium visual and tactile quality.',
                    ],
                ],
                'properties' => [
                    [
                        'key' => 'weight',
                        'label' => 'Weight',
                        'value' => 'Lightweight mineral composite',
                        'vs' => 'Suitable for portable premium objects, tabletop accessories, and interior accessory systems.',
                    ],
                    [
                        'key' => 'strength',
                        'label' => 'Strength',
                        'value' => 'High compressive stability',
                        'vs' => 'Built for premium display, tabletop, and light-use structural applications.',
                    ],
                    [
                        'key' => 'flexibility',
                        'label' => 'Forming flexibility',
                        'value' => 'Process-dependent tuning',
                        'vs' => 'Can be tuned across rigid and semi-rigid outputs depending on formulation and moulding parameters.',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => 'Water absorption',
                        'value' => '0.00% demo target',
                        'vs' => 'Demo figure for layout only. Replace with verified laboratory results before public certification claims.',
                    ],
                    [
                        'key' => 'surface',
                        'label' => 'Surface feel',
                        'value' => 'Fine mineral texture',
                        'vs' => 'Designed to communicate a premium shell-mineral tactility for interior and hospitality objects.',
                    ],
                    [
                        'key' => 'circularity',
                        'label' => 'Circular sourcing',
                        'value' => 'Recovered oyster shell input',
                        'vs' => 'Built around reuse, traceability, and circular material communication for premium brands.',
                    ],
                ],
                'models' => [
                    [
                        'id' => 'demo_pellet',
                        'name' => 'Demo pellet input',
                        'finish' => 'Provisional',
                        'gravity' => 1.5,
                        'description' => 'Example B2B feedstock record for layout and sampling discussions.',
                    ],
                    [
                        'id' => 'prototype_object',
                        'name' => 'Prototype object grade',
                        'finish' => 'Demo finish',
                        'gravity' => 1.6,
                        'description' => 'Prototype-grade material profile pending project-specific testing.',
                    ],
                ],
                'colors' => [
                    [
                        'id' => 'oyster_mineral',
                        'temp' => '200C',
                        'name' => 'Oyster mineral',
                        'description' => 'Warm mineral tone for hospitality and interior prototypes.',
                    ],
                    [
                        'id' => 'thermal_ash',
                        'temp' => '700C',
                        'name' => 'Thermal ash',
                        'description' => 'Grey shell-mineral tone for demo material storytelling.',
                    ],
                ],
            ],
            'zh' => [
                'name' => '高端牡蛎壳复合材料',
                'tagline' => '一种基于回收牡蛎壳、具备科学验证方向的高端材料平台。',
                'origin' => '面向高端室内物件、酒店餐饮项目、餐具概念以及未来联合产品开发。',
                'process_steps' => [
                    [
                        'step' => 1,
                        'title' => '牡蛎壳收集',
                        'body' => '从沿海与海产相关废弃物流中回收牡蛎壳，并进行分选以进入材料处理流程。',
                    ],
                    [
                        'step' => 2,
                        'title' => '清洗与热净化',
                        'body' => '牡蛎壳经过清洗、干燥与热处理，减少有机残留，并形成稳定的矿物基底。',
                    ],
                    [
                        'step' => 3,
                        'title' => '复合制粒',
                        'body' => '净化后的贝壳粉与随工艺调整的粘结体系混合，并制成稳定颗粒用于后续成型。',
                    ],
                    [
                        'step' => 4,
                        'title' => '压缩成型与表面处理',
                        'body' => '颗粒通过压缩成型转化为原型或可生产物件，并完成表面处理以呈现高端视觉与触感。',
                    ],
                ],
                'properties' => [
                    [
                        'key' => 'weight',
                        'label' => '重量',
                        'value' => '轻量化矿物复合材料',
                        'vs' => '适用于便携式高端物件、桌面配件和室内配饰系统。',
                    ],
                    [
                        'key' => 'strength',
                        'label' => '强度',
                        'value' => '高压缩稳定性',
                        'vs' => '适用于高端展示、桌面物件和轻量结构类应用。',
                    ],
                    [
                        'key' => 'flexibility',
                        'label' => '成型灵活性',
                        'value' => '可随工艺调整',
                        'vs' => '可根据配方与模具参数，在刚性与半刚性输出之间调整。',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => '吸水率',
                        'value' => '0.00% 演示目标',
                        'vs' => '此为页面展示用模拟数据。正式对外认证声明前必须替换为真实实验室结果。',
                    ],
                    [
                        'key' => 'surface',
                        'label' => '表面触感',
                        'value' => '细腻矿物质感',
                        'vs' => '用于传达适合室内与酒店餐饮物件的高端贝壳矿物触感。',
                    ],
                    [
                        'key' => 'circularity',
                        'label' => '循环来源',
                        'value' => '回收牡蛎壳原料',
                        'vs' => '围绕再利用、可追溯性和面向高端品牌的循环材料叙事构建。',
                    ],
                ],
                'models' => [
                    [
                        'id' => 'demo_pellet',
                        'name' => '演示颗粒原料',
                        'finish' => '临时数据',
                        'gravity' => 1.5,
                        'description' => '用于页面展示和样品讨论的 B2B 原料记录。',
                    ],
                    [
                        'id' => 'prototype_object',
                        'name' => '原型物件等级',
                        'finish' => '演示表面',
                        'gravity' => 1.6,
                        'description' => '原型级材料档案，最终数据需按项目测试确认。',
                    ],
                ],
                'colors' => [
                    [
                        'id' => 'oyster_mineral',
                        'temp' => '200C',
                        'name' => '牡蛎矿物白',
                        'description' => '适用于酒店餐饮与室内原型的温润矿物色调。',
                    ],
                    [
                        'id' => 'thermal_ash',
                        'temp' => '700C',
                        'name' => '热处理灰',
                        'description' => '用于演示材料叙事的灰色贝壳矿物色调。',
                    ],
                ],
            ],
            'ko' => [
                'name' => '프리미엄 굴 껍데기 복합소재',
                'tagline' => '회수한 굴 껍데기를 기반으로 한 과학 검증 지향의 프리미엄 소재 플랫폼입니다.',
                'origin' => '프리미엄 인테리어 오브젝트, 호스피탈리티 프로그램, 테이블웨어 콘셉트, 향후 공동 제품 개발에 적합하도록 설계되었습니다.',
                'process_steps' => [
                    [
                        'step' => 1,
                        'title' => '굴 껍데기 수거',
                        'body' => '연안 및 수산 관련 폐기물 흐름에서 회수한 굴 껍데기를 수거하고 소재 가공을 위해 선별합니다.',
                    ],
                    [
                        'step' => 2,
                        'title' => '세척 및 열 정화',
                        'body' => '굴 껍데기를 세척, 건조, 열처리하여 유기 잔류물을 줄이고 안정적인 미네랄 기반을 준비합니다.',
                    ],
                    [
                        'step' => 3,
                        'title' => '복합 펠릿화',
                        'body' => '정제된 쉘 파우더를 공정 조건에 맞춘 바인더 시스템과 혼합해 후속 성형용 균일 펠릿으로 만듭니다.',
                    ],
                    [
                        'step' => 4,
                        'title' => '압축 성형 및 마감',
                        'body' => '펠릿을 압축 성형해 프로토타입 또는 생산 가능한 오브젝트로 만들고, 프리미엄 시각 및 촉각 품질을 위해 마감합니다.',
                    ],
                ],
                'properties' => [
                    [
                        'key' => 'weight',
                        'label' => '무게',
                        'value' => '경량 미네랄 복합소재',
                        'vs' => '휴대 가능한 프리미엄 오브젝트, 테이블 액세서리, 인테리어 액세서리 시스템에 적합합니다.',
                    ],
                    [
                        'key' => 'strength',
                        'label' => '강도',
                        'value' => '높은 압축 안정성',
                        'vs' => '프리미엄 디스플레이, 테이블웨어, 경량 구조 용도에 적합합니다.',
                    ],
                    [
                        'key' => 'flexibility',
                        'label' => '성형 유연성',
                        'value' => '공정 조건에 따른 조정',
                        'vs' => '배합과 성형 조건에 따라 강성 및 반강성 결과물로 조정할 수 있습니다.',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => '흡수율',
                        'value' => '0.00% 데모 목표',
                        'vs' => '화면 구성을 위한 예시 수치입니다. 공개 인증 주장 전 실제 시험 결과로 교체해야 합니다.',
                    ],
                    [
                        'key' => 'surface',
                        'label' => '표면 질감',
                        'value' => '섬세한 미네랄 텍스처',
                        'vs' => '인테리어 및 호스피탈리티 오브젝트에 어울리는 프리미엄 쉘 미네랄 촉감을 전달합니다.',
                    ],
                    [
                        'key' => 'circularity',
                        'label' => '순환 원료',
                        'value' => '회수 굴 껍데기 원료',
                        'vs' => '재사용, 추적 가능성, 프리미엄 브랜드를 위한 순환 소재 커뮤니케이션을 중심으로 구성됩니다.',
                    ],
                ],
                'models' => [
                    [
                        'id' => 'demo_pellet',
                        'name' => '데모 펠릿 원료',
                        'finish' => '임시 데이터',
                        'gravity' => 1.5,
                        'description' => '화면 구성과 샘플 논의를 위한 B2B 원료 예시 기록입니다.',
                    ],
                    [
                        'id' => 'prototype_object',
                        'name' => '프로토타입 오브젝트 등급',
                        'finish' => '데모 마감',
                        'gravity' => 1.6,
                        'description' => '프로토타입 수준 소재 프로필이며 최종 데이터는 프로젝트별 시험으로 확인해야 합니다.',
                    ],
                ],
                'colors' => [
                    [
                        'id' => 'oyster_mineral',
                        'temp' => '200C',
                        'name' => '오이스터 미네랄',
                        'description' => '호스피탈리티 및 인테리어 프로토타입에 어울리는 따뜻한 미네랄 톤입니다.',
                    ],
                    [
                        'id' => 'thermal_ash',
                        'temp' => '700C',
                        'name' => '써멀 애시',
                        'description' => '데모 소재 스토리텔링을 위한 회색 쉘 미네랄 톤입니다.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function payloads(): array
    {
        return [
            'en' => [
                'name' => 'OXP',
                'tagline' => "Ocean's Legacy, Crafted with Artisan Tech.",
                'origin' => 'Recycled oyster shells collected from coastal waste streams',
                'process_steps' => [
                    [
                        'step' => 1,
                        'title' => 'Collection',
                        'body' => 'Discarded oyster shells gathered from seafood industry waste',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Thermal Purification',
                        'body' => 'Shells treated between 200C-700C, carbonising organic matter',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Pelletisation',
                        'body' => 'Purified shell material formed into uniform pellets',
                    ],
                    [
                        'step' => 4,
                        'title' => 'Compression Moulding',
                        'body' => 'Pellets compressed under high heat into final product form',
                    ],
                ],
                'properties' => [
                    [
                        'key' => 'weight',
                        'label' => 'Lightweight',
                        'value' => 'Lower-density target',
                        'vs' => 'Depends on final formulation and product geometry',
                    ],
                    [
                        'key' => 'strength',
                        'label' => 'Durability',
                        'value' => 'Compression-moulding compatible',
                        'vs' => 'Requires final application testing',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => 'Water Resistance',
                        'value' => 'Testing pending',
                        'vs' => 'Depends on final formulation and test conditions',
                    ],
                    [
                        'key' => 'antibacterial',
                        'label' => 'Surface Hygiene Review',
                        'value' => 'Application testing required',
                        'vs' => 'Food-contact claims need approved documents',
                    ],
                    [
                        'key' => 'grip',
                        'label' => 'Mineral Grip',
                        'value' => 'Fine mineral texture surface',
                        'vs' => 'Slip resistance depends on final finish',
                    ],
                    [
                        'key' => 'otr',
                        'label' => 'Selective Flow',
                        'value' => 'Data available on request',
                        'vs' => 'Barrier data depends on application testing',
                    ],
                ],
                'certifications' => [],
                'models' => [
                    [
                        'id' => 'lite_15',
                        'name' => '1.5 Lite',
                        'finish' => 'Glossy',
                        'gravity' => 1.5,
                        'description' => 'Smooth brilliance, light daily life',
                    ],
                    [
                        'id' => 'heritage_16',
                        'name' => '1.6 Heritage',
                        'finish' => 'Matte',
                        'gravity' => 1.6,
                        'description' => 'Deep matte texture, stable tranquility',
                    ],
                ],
                'colors' => [
                    [
                        'id' => 'ocean_bone',
                        'temp' => '200C',
                        'name' => 'Ocean Bone',
                        'description' => 'Warm white, the inherent purity of seashells',
                    ],
                    [
                        'id' => 'forged_ash',
                        'temp' => '700C',
                        'name' => 'Forged Ash',
                        'description' => 'Serene grey, all organic matter emptied by heat',
                    ],
                ],
            ],
            'zh' => [
                'name' => 'OXP',
                'tagline' => '海洋遗产，以匠人工艺重塑。',
                'origin' => '从沿海废弃物流中回收的牡蛎壳',
                'process_steps' => [
                    [
                        'step' => 1,
                        'title' => '收集',
                        'body' => '从海产加工废弃物中收集废弃牡蛎壳',
                    ],
                    [
                        'step' => 2,
                        'title' => '热净化',
                        'body' => '在 200°C–700°C 之间处理贝壳，使有机物碳化并完成净化',
                    ],
                    [
                        'step' => 3,
                        'title' => '制粒',
                        'body' => '将净化后的贝壳材料制成稳定均匀的颗粒',
                    ],
                    [
                        'step' => 4,
                        'title' => '压缩成型',
                        'body' => '在高温与压力下将颗粒压制成最终产品形态',
                    ],
                ],
                'properties' => [
                    [
                        'key' => 'weight',
                        'label' => '轻量化',
                        'value' => '低密度目标',
                        'vs' => '取决于最终配方与产品结构',
                    ],
                    [
                        'key' => 'strength',
                        'label' => '耐用性',
                        'value' => '适配压缩成型',
                        'vs' => '需要按最终应用测试',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => '耐水性',
                        'value' => '测试待确认',
                        'vs' => '取决于最终配方和测试条件',
                    ],
                    [
                        'key' => 'antibacterial',
                        'label' => '表面卫生评估',
                        'value' => '需按应用测试',
                        'vs' => '食品接触声明需有已批准文件',
                    ],
                    [
                        'key' => 'grip',
                        'label' => '矿物防滑触感',
                        'value' => '细腻矿物纹理表面',
                        'vs' => '防滑表现取决于最终表面处理',
                    ],
                    [
                        'key' => 'otr',
                        'label' => '选择性透气',
                        'value' => '数据可按需提供',
                        'vs' => '阻隔数据取决于应用测试',
                    ],
                ],
                'certifications' => [],
                'models' => [
                    [
                        'id' => 'lite_15',
                        'name' => '1.5 轻盈版',
                        'finish' => '亮面',
                        'gravity' => 1.5,
                        'description' => '光洁明亮，轻盈日常',
                    ],
                    [
                        'id' => 'heritage_16',
                        'name' => '1.6 经典版',
                        'finish' => '哑光',
                        'gravity' => 1.6,
                        'description' => '深哑光质感，沉稳安静',
                    ],
                ],
                'colors' => [
                    [
                        'id' => 'ocean_bone',
                        'temp' => '200C',
                        'name' => '海骨白',
                        'description' => '温润白色，保留贝壳本身的纯净感',
                    ],
                    [
                        'id' => 'forged_ash',
                        'temp' => '700C',
                        'name' => '煅灰',
                        'description' => '安静灰调，经热处理去除有机物后的沉稳质感',
                    ],
                ],
            ],
            'ko' => [
                'name' => 'OXP',
                'tagline' => '바다의 유산을 장인의 기술로 다시 빚다.',
                'origin' => '해안 폐기물 흐름에서 회수한 굴 껍데기',
                'process_steps' => [
                    [
                        'step' => 1,
                        'title' => '수거',
                        'body' => '수산물 가공 과정에서 나온 폐굴 껍데기를 수거합니다',
                    ],
                    [
                        'step' => 2,
                        'title' => '열 정화',
                        'body' => '200°C–700°C에서 껍데기를 처리해 유기물을 탄화시키고 정화합니다',
                    ],
                    [
                        'step' => 3,
                        'title' => '펠릿화',
                        'body' => '정화된 껍데기 소재를 균일한 펠릿으로 만듭니다',
                    ],
                    [
                        'step' => 4,
                        'title' => '압축 성형',
                        'body' => '펠릿을 고온과 압력으로 눌러 최종 제품 형태로 성형합니다',
                    ],
                ],
                'properties' => [
                    [
                        'key' => 'weight',
                        'label' => '경량',
                        'value' => '저밀도 목표',
                        'vs' => '최종 배합과 제품 형상에 따라 달라짐',
                    ],
                    [
                        'key' => 'strength',
                        'label' => '내구성',
                        'value' => '압축성형 호환',
                        'vs' => '최종 적용 시험 필요',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => '내수성',
                        'value' => '시험 대기',
                        'vs' => '최종 배합과 시험 조건에 따라 달라짐',
                    ],
                    [
                        'key' => 'antibacterial',
                        'label' => '표면 위생 검토',
                        'value' => '적용 시험 필요',
                        'vs' => '식품 접촉 주장은 승인 문서 필요',
                    ],
                    [
                        'key' => 'grip',
                        'label' => '미네랄 그립',
                        'value' => '미세한 미네랄 질감 표면',
                        'vs' => '미끄럼 저항은 최종 마감에 따라 달라짐',
                    ],
                    [
                        'key' => 'otr',
                        'label' => '선택적 투과',
                        'value' => '자료 요청 가능',
                        'vs' => '차단 데이터는 적용 시험에 따라 달라짐',
                    ],
                ],
                'certifications' => [],
                'models' => [
                    [
                        'id' => 'lite_15',
                        'name' => '1.5 라이트',
                        'finish' => '유광',
                        'gravity' => 1.5,
                        'description' => '매끄럽고 밝은 광택, 가벼운 일상',
                    ],
                    [
                        'id' => 'heritage_16',
                        'name' => '1.6 헤리티지',
                        'finish' => '무광',
                        'gravity' => 1.6,
                        'description' => '깊은 무광 질감과 차분한 안정감',
                    ],
                ],
                'colors' => [
                    [
                        'id' => 'ocean_bone',
                        'temp' => '200C',
                        'name' => '오션 본',
                        'description' => '따뜻한 화이트, 조개껍데기 본연의 순수함',
                    ],
                    [
                        'id' => 'forged_ash',
                        'temp' => '700C',
                        'name' => '포지드 애시',
                        'description' => '차분한 그레이, 열처리로 유기물이 비워진 안정적인 질감',
                    ],
                ],
            ],
        ];
    }
}
