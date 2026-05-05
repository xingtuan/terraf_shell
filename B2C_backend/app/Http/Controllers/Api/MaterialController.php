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
        $title = LocalizedContent::resolveString(
            $material->title_translations,
            $locale,
            $material->title,
        ) ?? $fallback['name'];
        $headline = LocalizedContent::resolveString(
            $material->headline_translations,
            $locale,
            $material->headline,
        );
        $summary = LocalizedContent::resolveString(
            $material->summary_translations,
            $locale,
            $material->summary,
        );

        $processSteps = $material->storySections
            ->values()
            ->map(fn ($section, int $index): array => [
                'step' => $index + 1,
                'title' => LocalizedContent::resolveString(
                    $section->title_translations,
                    $locale,
                    $section->title,
                ) ?? (string) ($index + 1),
                'body' => LocalizedContent::resolveString(
                    $section->content_translations,
                    $locale,
                    $section->content,
                ) ?? '',
            ])
            ->filter(fn (array $step): bool => trim((string) $step['title']) !== '' || trim((string) $step['body']) !== '')
            ->values()
            ->all();

        $properties = $material->specs
            ->values()
            ->map(fn ($spec): array => [
                'key' => $spec->key ?? '',
                'label' => LocalizedContent::resolveString(
                    $spec->label_translations,
                    $locale,
                    $spec->label,
                ) ?? '',
                'value' => trim(collect([
                    LocalizedContent::resolveString($spec->value_translations, $locale, $spec->value),
                    $spec->unit,
                ])->filter()->implode(' ')),
                'vs' => LocalizedContent::resolveString(
                    $spec->detail_translations,
                    $locale,
                    $spec->detail,
                ) ?? '',
            ])
            ->filter(fn (array $property): bool => trim((string) $property['label']) !== '' || trim((string) $property['value']) !== '')
            ->values()
            ->all();

        return [
            'name' => $title,
            'tagline' => $headline ?: $title,
            'origin' => $summary ?: ($fallback['origin'] ?? ''),
            'process_steps' => $processSteps !== [] ? $processSteps : $fallback['process_steps'],
            'properties' => $properties !== [] ? $properties : $fallback['properties'],
            'certifications' => is_array($material->certifications) ? $material->certifications : [],
            'technical_downloads' => is_array($material->technical_downloads) ? $material->technical_downloads : [],
            'models' => $fallback['models'],
            'colors' => $fallback['colors'],
        ];
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
        $payload = $this->payloads()[$locale] ?? $this->payloads()['en'];

        $payload['certifications'] = [];
        $payload['technical_downloads'] = [];

        return $payload;
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
