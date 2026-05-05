<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse($this->payload($this->locale($request)));
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        return $this->successResponse($this->payload($this->locale($request)));
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
        return $this->payloads()[$locale] ?? $this->payloads()['en'];
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
                        'value' => '1.5-1.6 specific gravity',
                        'vs' => '35% lighter than ceramic (2.4)',
                    ],
                    [
                        'key' => 'strength',
                        'label' => 'Impact Resistant',
                        'value' => 'Unbreakable integrity',
                        'vs' => 'Overcomes ceramic chipping & cracking',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => 'Zero Absorption',
                        'value' => '0.00% water absorption',
                        'vs' => 'No odour, no staining, no bacteria',
                    ],
                    [
                        'key' => 'antibacterial',
                        'label' => 'Natural Antibacterial',
                        'value' => 'Weak alkaline inhibition',
                        'vs' => 'No artificial coatings needed',
                    ],
                    [
                        'key' => 'grip',
                        'label' => 'Mineral Grip',
                        'value' => 'Fine mineral texture surface',
                        'vs' => 'Non-slip even when wet with soap',
                    ],
                    [
                        'key' => 'otr',
                        'label' => 'Selective Flow',
                        'value' => 'OTR 500 cc/m2/day',
                        'vs' => 'Breathable yet moisture-blocking',
                    ],
                ],
                'certifications' => [
                    [
                        'key' => 'absorption',
                        'label' => 'Water Absorption Test',
                        'value' => '0.00%',
                    ],
                    [
                        'key' => 'toxicity',
                        'label' => 'Toxicity Test',
                        'value' => 'Zero heavy metals, zero microplastics',
                    ],
                    [
                        'key' => 'acid',
                        'label' => 'Acid/Corrosion Resistance',
                        'value' => 'No surface degradation',
                    ],
                    [
                        'key' => 'fire',
                        'label' => 'Non-Toxic Fireproof',
                        'value' => 'Non-flammable, zero toxic gas',
                    ],
                    [
                        'key' => 'otr',
                        'label' => 'OTR Data',
                        'value' => '500 cc/m2/day certified',
                    ],
                ],
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
                        'value' => '比重 1.5–1.6',
                        'vs' => '比传统陶瓷（2.4）轻约 35%',
                    ],
                    [
                        'key' => 'strength',
                        'label' => '抗冲击',
                        'value' => '高强度整体结构',
                        'vs' => '减少陶瓷常见的崩边与开裂问题',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => '零吸水',
                        'value' => '0.00% 吸水率',
                        'vs' => '不易残留气味、污渍与细菌',
                    ],
                    [
                        'key' => 'antibacterial',
                        'label' => '天然抗菌',
                        'value' => '弱碱性抑菌',
                        'vs' => '无需人工抗菌涂层',
                    ],
                    [
                        'key' => 'grip',
                        'label' => '矿物防滑触感',
                        'value' => '细腻矿物纹理表面',
                        'vs' => '即使沾水或皂液也能保持防滑',
                    ],
                    [
                        'key' => 'otr',
                        'label' => '选择性透气',
                        'value' => 'OTR 500 cc/m²/天',
                        'vs' => '可透气，同时阻隔湿气',
                    ],
                ],
                'certifications' => [
                    [
                        'key' => 'absorption',
                        'label' => '吸水率测试',
                        'value' => '0.00%',
                    ],
                    [
                        'key' => 'toxicity',
                        'label' => '毒性测试',
                        'value' => '零重金属、零微塑料',
                    ],
                    [
                        'key' => 'acid',
                        'label' => '耐酸/耐腐蚀',
                        'value' => '表面无降解',
                    ],
                    [
                        'key' => 'fire',
                        'label' => '无毒防火',
                        'value' => '不燃，无有毒气体',
                    ],
                    [
                        'key' => 'otr',
                        'label' => 'OTR 数据',
                        'value' => '认证 500 cc/m²/天',
                    ],
                ],
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
                        'value' => '비중 1.5–1.6',
                        'vs' => '기존 세라믹(2.4)보다 약 35% 가벼움',
                    ],
                    [
                        'key' => 'strength',
                        'label' => '충격 저항성',
                        'value' => '쉽게 깨지지 않는 일체 구조',
                        'vs' => '세라믹의 모서리 깨짐과 균열 문제를 줄임',
                    ],
                    [
                        'key' => 'absorption',
                        'label' => '무흡수',
                        'value' => '흡수율 0.00%',
                        'vs' => '냄새, 얼룩, 세균이 남기 어려움',
                    ],
                    [
                        'key' => 'antibacterial',
                        'label' => '천연 항균',
                        'value' => '약알칼리성 항균 작용',
                        'vs' => '인공 항균 코팅 불필요',
                    ],
                    [
                        'key' => 'grip',
                        'label' => '미네랄 그립',
                        'value' => '미세한 미네랄 질감 표면',
                        'vs' => '물이나 비누가 닿아도 미끄러짐을 줄임',
                    ],
                    [
                        'key' => 'otr',
                        'label' => '선택적 투과',
                        'value' => 'OTR 500 cc/m²/일',
                        'vs' => '숨은 통하게 하고 습기는 막음',
                    ],
                ],
                'certifications' => [
                    [
                        'key' => 'absorption',
                        'label' => '흡수율 시험',
                        'value' => '0.00%',
                    ],
                    [
                        'key' => 'toxicity',
                        'label' => '독성 시험',
                        'value' => '중금속 0, 미세플라스틱 0',
                    ],
                    [
                        'key' => 'acid',
                        'label' => '내산/내식성',
                        'value' => '표면 열화 없음',
                    ],
                    [
                        'key' => 'fire',
                        'label' => '무독성 난연',
                        'value' => '불연성, 유독 가스 없음',
                    ],
                    [
                        'key' => 'otr',
                        'label' => 'OTR 데이터',
                        'value' => '500 cc/m²/일 인증',
                    ],
                ],
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
