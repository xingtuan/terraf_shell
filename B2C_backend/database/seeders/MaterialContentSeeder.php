<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use Illuminate\Database\Seeder;

class MaterialContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publishedAt = now();

        $material = Material::query()->updateOrCreate(
            ['slug' => 'premium-oyster-shell'],
            [
                'title' => 'Premium Oyster Shell Composite',
                'title_translations' => [
                    'en' => 'Premium Oyster Shell Composite',
                    'zh' => '高端牡蛎壳复合材料',
                    'ko' => '프리미엄 굴 껍데기 복합소재',
                ],
                'headline' => 'A premium, science-backed material platform built from recovered oyster shell.',
                'headline_translations' => [
                    'en' => 'A premium, science-backed material platform built from recovered oyster shell.',
                    'zh' => '一种基于回收牡蛎壳、具备科学验证方向的高端材料平台。',
                    'ko' => '회수한 굴 껍데기를 기반으로 한 과학 검증 지향의 프리미엄 소재 플랫폼입니다.',
                ],
                'summary' => 'Designed for premium interior objects, hospitality programs, tableware concepts, and future collaborative product development.',
                'summary_translations' => [
                    'en' => 'Designed for premium interior objects, hospitality programs, tableware concepts, and future collaborative product development.',
                    'zh' => '面向高端室内物件、酒店餐饮项目、餐具概念以及未来联合产品开发。',
                    'ko' => '프리미엄 인테리어 오브젝트, 호스피탈리티 프로그램, 테이블웨어 콘셉트, 향후 공동 제품 개발에 적합하도록 설계되었습니다.',
                ],
                'story_overview' => 'Recovered oyster shells are cleaned, thermally purified, pelletised, and compression-moulded into a premium mineral composite for product and material collaborations.',
                'story_overview_translations' => [
                    'en' => 'Recovered oyster shells are cleaned, thermally purified, pelletised, and compression-moulded into a premium mineral composite for product and material collaborations.',
                    'zh' => '回收牡蛎壳经过清洗、热净化、制粒与压缩成型，转化为适用于产品与材料合作的高端矿物复合材料。',
                    'ko' => '회수한 굴 껍데기를 세척, 열 정화, 펠릿화, 압축 성형 과정을 거쳐 제품 및 소재 협업에 적합한 프리미엄 미네랄 복합소재로 전환합니다.',
                ],
                'science_overview' => 'Demo material data combines lightweight handling, compressive stability, low water absorption targets, mineral texture, and circular sourcing. Final claims must be validated against project-specific testing.',
                'science_overview_translations' => [
                    'en' => 'Demo material data combines lightweight handling, compressive stability, low water absorption targets, mineral texture, and circular sourcing. Final claims must be validated against project-specific testing.',
                    'zh' => '当前为演示材料数据，涵盖轻量化手感、压缩稳定性、低吸水目标、矿物触感与循环来源叙事。最终性能声明需以具体项目测试为准。',
                    'ko' => '현재 데이터는 데모용 소재 정보이며, 경량감, 압축 안정성, 낮은 흡수율 목표, 미네랄 질감, 순환 원료 스토리를 포함합니다. 최종 성능 주장은 프로젝트별 테스트로 검증해야 합니다.',
                ],
                'certifications' => [
                    [
                        'key' => 'water_absorption',
                        'label' => 'Water absorption test',
                        'label_translations' => [
                            'en' => 'Water absorption test',
                            'zh' => '吸水率测试',
                            'ko' => '흡수율 테스트',
                        ],
                        'name' => 'Water absorption test',
                        'name_translations' => [
                            'en' => 'Water absorption test',
                            'zh' => '吸水率测试',
                            'ko' => '흡수율 테스트',
                        ],
                        'value' => '0.00% demo target',
                        'value_translations' => [
                            'en' => '0.00% demo target',
                            'zh' => '0.00% 演示目标',
                            'ko' => '0.00% 데모 목표',
                        ],
                        'result' => '0.00% demo target',
                        'result_translations' => [
                            'en' => '0.00% demo target',
                            'zh' => '0.00% 演示目标',
                            'ko' => '0.00% 데모 목표',
                        ],
                        'status' => 'demo',
                        'verified' => false,
                    ],
                    [
                        'key' => 'compressive_stability',
                        'label' => 'Compressive stability',
                        'label_translations' => [
                            'en' => 'Compressive stability',
                            'zh' => '压缩稳定性',
                            'ko' => '압축 안정성',
                        ],
                        'name' => 'Compressive stability',
                        'name_translations' => [
                            'en' => 'Compressive stability',
                            'zh' => '压缩稳定性',
                            'ko' => '압축 안정성',
                        ],
                        'value' => 'Prototype-grade stability',
                        'value_translations' => [
                            'en' => 'Prototype-grade stability',
                            'zh' => '原型级稳定性',
                            'ko' => '프로토타입 수준 안정성',
                        ],
                        'result' => 'Prototype-grade stability',
                        'result_translations' => [
                            'en' => 'Prototype-grade stability',
                            'zh' => '原型级稳定性',
                            'ko' => '프로토타입 수준 안정성',
                        ],
                        'status' => 'demo',
                        'verified' => false,
                    ],
                    [
                        'key' => 'surface_safety',
                        'label' => 'Surface safety review',
                        'label_translations' => [
                            'en' => 'Surface safety review',
                            'zh' => '表面安全性评估',
                            'ko' => '표면 안전성 검토',
                        ],
                        'name' => 'Surface safety review',
                        'name_translations' => [
                            'en' => 'Surface safety review',
                            'zh' => '表面安全性评估',
                            'ko' => '표면 안전성 검토',
                        ],
                        'value' => 'Pending final lab report',
                        'value_translations' => [
                            'en' => 'Pending final lab report',
                            'zh' => '等待最终实验室报告',
                            'ko' => '최종 시험 보고서 대기 중',
                        ],
                        'result' => 'Pending final lab report',
                        'result_translations' => [
                            'en' => 'Pending final lab report',
                            'zh' => '等待最终实验室报告',
                            'ko' => '최종 시험 보고서 대기 중',
                        ],
                        'status' => 'demo',
                        'verified' => false,
                    ],
                    [
                        'key' => 'material_origin',
                        'label' => 'Recovered shell origin',
                        'label_translations' => [
                            'en' => 'Recovered shell origin',
                            'zh' => '回收牡蛎壳来源',
                            'ko' => '회수 굴 껍데기 원료',
                        ],
                        'name' => 'Recovered shell origin',
                        'name_translations' => [
                            'en' => 'Recovered shell origin',
                            'zh' => '回收牡蛎壳来源',
                            'ko' => '회수 굴 껍데기 원료',
                        ],
                        'value' => 'Traceable coastal waste stream',
                        'value_translations' => [
                            'en' => 'Traceable coastal waste stream',
                            'zh' => '可追溯沿海废弃物流',
                            'ko' => '추적 가능한 연안 폐기물 흐름',
                        ],
                        'result' => 'Traceable coastal waste stream',
                        'result_translations' => [
                            'en' => 'Traceable coastal waste stream',
                            'zh' => '可追溯沿海废弃物流',
                            'ko' => '추적 가능한 연안 폐기물 흐름',
                        ],
                        'status' => 'demo',
                        'verified' => false,
                    ],
                    [
                        'key' => 'thermal_process',
                        'label' => 'Thermal process window',
                        'label_translations' => [
                            'en' => 'Thermal process window',
                            'zh' => '热处理工艺窗口',
                            'ko' => '열처리 공정 범위',
                        ],
                        'name' => 'Thermal process window',
                        'name_translations' => [
                            'en' => 'Thermal process window',
                            'zh' => '热处理工艺窗口',
                            'ko' => '열처리 공정 범위',
                        ],
                        'value' => '200°C–700°C process range',
                        'value_translations' => [
                            'en' => '200°C–700°C process range',
                            'zh' => '200°C–700°C 工艺范围',
                            'ko' => '200°C–700°C 공정 범위',
                        ],
                        'result' => '200°C–700°C process range',
                        'result_translations' => [
                            'en' => '200°C–700°C process range',
                            'zh' => '200°C–700°C 工艺范围',
                            'ko' => '200°C–700°C 공정 범위',
                        ],
                        'status' => 'demo',
                        'verified' => false,
                    ],
                    [
                        'key' => 'demo_disclaimer',
                        'label' => 'Verification status',
                        'label_translations' => [
                            'en' => 'Verification status',
                            'zh' => '验证状态',
                            'ko' => '검증 상태',
                        ],
                        'name' => 'Verification status',
                        'name_translations' => [
                            'en' => 'Verification status',
                            'zh' => '验证状态',
                            'ko' => '검증 상태',
                        ],
                        'value' => 'Demo data — replace before final publication',
                        'value_translations' => [
                            'en' => 'Demo data — replace before final publication',
                            'zh' => '演示数据——正式发布前需替换',
                            'ko' => '데모 데이터 — 최종 공개 전 교체 필요',
                        ],
                        'result' => 'Demo data — replace before final publication',
                        'result_translations' => [
                            'en' => 'Demo data — replace before final publication',
                            'zh' => '演示数据——正式发布前需替换',
                            'ko' => '데모 데이터 — 최종 공개 전 교체 필요',
                        ],
                        'status' => 'demo',
                        'verified' => false,
                    ],
                ],
                'technical_downloads' => [],
                'status' => PublishStatus::Published->value,
                'is_featured' => true,
                'sort_order' => 1,
                'published_at' => $publishedAt,
            ]
        );

        $specs = [
            [
                'key' => 'weight',
                'label' => 'Weight',
                'label_translations' => [
                    'en' => 'Weight',
                    'zh' => '重量',
                    'ko' => '무게',
                ],
                'value' => 'Lightweight mineral composite',
                'value_translations' => [
                    'en' => 'Lightweight mineral composite',
                    'zh' => '轻量化矿物复合材料',
                    'ko' => '경량 미네랄 복합소재',
                ],
                'detail' => 'Suitable for portable premium objects, tabletop accessories, and interior accessory systems.',
                'detail_translations' => [
                    'en' => 'Suitable for portable premium objects, tabletop accessories, and interior accessory systems.',
                    'zh' => '适用于便携式高端物件、桌面配件和室内配饰系统。',
                    'ko' => '휴대 가능한 프리미엄 오브젝트, 테이블 액세서리, 인테리어 액세서리 시스템에 적합합니다.',
                ],
                'sort_order' => 1,
            ],
            [
                'key' => 'strength',
                'label' => 'Strength',
                'label_translations' => [
                    'en' => 'Strength',
                    'zh' => '强度',
                    'ko' => '강도',
                ],
                'value' => 'High compressive stability',
                'value_translations' => [
                    'en' => 'High compressive stability',
                    'zh' => '高压缩稳定性',
                    'ko' => '높은 압축 안정성',
                ],
                'detail' => 'Built for premium display, tabletop, and light-use structural applications.',
                'detail_translations' => [
                    'en' => 'Built for premium display, tabletop, and light-use structural applications.',
                    'zh' => '适用于高端展示、桌面物件和轻量结构类应用。',
                    'ko' => '프리미엄 디스플레이, 테이블웨어, 경량 구조 용도에 적합합니다.',
                ],
                'sort_order' => 2,
            ],
            [
                'key' => 'flexibility',
                'label' => 'Forming flexibility',
                'label_translations' => [
                    'en' => 'Forming flexibility',
                    'zh' => '成型灵活性',
                    'ko' => '성형 유연성',
                ],
                'value' => 'Process-dependent tuning',
                'value_translations' => [
                    'en' => 'Process-dependent tuning',
                    'zh' => '可随工艺调整',
                    'ko' => '공정 조건에 따른 조정',
                ],
                'detail' => 'Can be tuned across rigid and semi-rigid outputs depending on formulation and moulding parameters.',
                'detail_translations' => [
                    'en' => 'Can be tuned across rigid and semi-rigid outputs depending on formulation and moulding parameters.',
                    'zh' => '可根据配方与模具参数，在刚性与半刚性输出之间调整。',
                    'ko' => '배합과 성형 조건에 따라 강성 및 반강성 결과물로 조정할 수 있습니다.',
                ],
                'sort_order' => 3,
            ],
            [
                'key' => 'absorption',
                'label' => 'Water absorption',
                'label_translations' => [
                    'en' => 'Water absorption',
                    'zh' => '吸水率',
                    'ko' => '흡수율',
                ],
                'value' => '0.00% demo target',
                'value_translations' => [
                    'en' => '0.00% demo target',
                    'zh' => '0.00% 演示目标',
                    'ko' => '0.00% 데모 목표',
                ],
                'detail' => 'Demo figure for layout only. Replace with verified laboratory results before public certification claims.',
                'detail_translations' => [
                    'en' => 'Demo figure for layout only. Replace with verified laboratory results before public certification claims.',
                    'zh' => '此为页面展示用模拟数据。正式对外认证声明前必须替换为真实实验室结果。',
                    'ko' => '화면 구성을 위한 예시 수치입니다. 공개 인증 주장 전 실제 시험 결과로 교체해야 합니다.',
                ],
                'sort_order' => 4,
            ],
            [
                'key' => 'surface',
                'label' => 'Surface feel',
                'label_translations' => [
                    'en' => 'Surface feel',
                    'zh' => '表面触感',
                    'ko' => '표면 질감',
                ],
                'value' => 'Fine mineral texture',
                'value_translations' => [
                    'en' => 'Fine mineral texture',
                    'zh' => '细腻矿物质感',
                    'ko' => '섬세한 미네랄 텍스처',
                ],
                'detail' => 'Designed to communicate a premium shell-mineral tactility for interior and hospitality objects.',
                'detail_translations' => [
                    'en' => 'Designed to communicate a premium shell-mineral tactility for interior and hospitality objects.',
                    'zh' => '用于传达适合室内与酒店餐饮物件的高端贝壳矿物触感。',
                    'ko' => '인테리어 및 호스피탈리티 오브젝트에 어울리는 프리미엄 쉘 미네랄 촉감을 전달합니다.',
                ],
                'sort_order' => 5,
            ],
            [
                'key' => 'circularity',
                'label' => 'Circular sourcing',
                'label_translations' => [
                    'en' => 'Circular sourcing',
                    'zh' => '循环来源',
                    'ko' => '순환 원료',
                ],
                'value' => 'Recovered oyster shell input',
                'value_translations' => [
                    'en' => 'Recovered oyster shell input',
                    'zh' => '回收牡蛎壳原料',
                    'ko' => '회수 굴 껍데기 원료',
                ],
                'detail' => 'Built around reuse, traceability, and circular material communication for premium brands.',
                'detail_translations' => [
                    'en' => 'Built around reuse, traceability, and circular material communication for premium brands.',
                    'zh' => '围绕再利用、可追溯性和面向高端品牌的循环材料叙事构建。',
                    'ko' => '재사용, 추적 가능성, 프리미엄 브랜드를 위한 순환 소재 커뮤니케이션을 중심으로 구성됩니다.',
                ],
                'sort_order' => 6,
            ],
        ];

        foreach ($specs as $spec) {
            MaterialSpec::query()->updateOrCreate(
                [
                    'material_id' => $material->id,
                    'key' => $spec['key'],
                ],
                [
                    'label' => $spec['label'],
                    'label_translations' => $spec['label_translations'],
                    'value' => $spec['value'],
                    'value_translations' => $spec['value_translations'],
                    'detail' => $spec['detail'],
                    'detail_translations' => $spec['detail_translations'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $spec['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

        MaterialSpec::query()
            ->where('material_id', $material->id)
            ->whereNotIn('key', array_column($specs, 'key'))
            ->delete();

        $storySections = [
            [
                'title' => 'Shell collection',
                'title_translations' => [
                    'en' => 'Shell collection',
                    'zh' => '牡蛎壳收集',
                    'ko' => '굴 껍데기 수거',
                ],
                'subtitle' => 'Recovered feedstock',
                'subtitle_translations' => [
                    'en' => 'Recovered feedstock',
                    'zh' => '回收原料',
                    'ko' => '회수 원료',
                ],
                'content' => 'Recovered oyster shells are collected from coastal and seafood-related waste streams, then sorted for material processing.',
                'content_translations' => [
                    'en' => 'Recovered oyster shells are collected from coastal and seafood-related waste streams, then sorted for material processing.',
                    'zh' => '从沿海与海产相关废弃物流中回收牡蛎壳，并进行分选以进入材料处理流程。',
                    'ko' => '연안 및 수산 관련 폐기물 흐름에서 회수한 굴 껍데기를 수거하고 소재 가공을 위해 선별합니다.',
                ],
                'highlight' => 'Recovered waste stream',
                'highlight_translations' => [
                    'en' => 'Recovered waste stream',
                    'zh' => '回收废弃物流',
                    'ko' => '회수 폐기물 흐름',
                ],
                'sort_order' => 1,
            ],
            [
                'title' => 'Cleaning and thermal purification',
                'title_translations' => [
                    'en' => 'Cleaning and thermal purification',
                    'zh' => '清洗与热净化',
                    'ko' => '세척 및 열 정화',
                ],
                'subtitle' => 'Stable mineral base',
                'subtitle_translations' => [
                    'en' => 'Stable mineral base',
                    'zh' => '稳定矿物基底',
                    'ko' => '안정적인 미네랄 기반',
                ],
                'content' => 'Shells are washed, dried, and thermally treated to reduce organic residue and prepare a stable mineral base.',
                'content_translations' => [
                    'en' => 'Shells are washed, dried, and thermally treated to reduce organic residue and prepare a stable mineral base.',
                    'zh' => '牡蛎壳经过清洗、干燥与热处理，减少有机残留，并形成稳定的矿物基底。',
                    'ko' => '굴 껍데기를 세척, 건조, 열처리하여 유기 잔류물을 줄이고 안정적인 미네랄 기반을 준비합니다.',
                ],
                'highlight' => 'Thermal purification',
                'highlight_translations' => [
                    'en' => 'Thermal purification',
                    'zh' => '热净化',
                    'ko' => '열 정화',
                ],
                'sort_order' => 2,
            ],
            [
                'title' => 'Pellet compounding',
                'title_translations' => [
                    'en' => 'Pellet compounding',
                    'zh' => '复合制粒',
                    'ko' => '복합 펠릿화',
                ],
                'subtitle' => 'Process-dependent binder system',
                'subtitle_translations' => [
                    'en' => 'Process-dependent binder system',
                    'zh' => '随工艺调整的粘结体系',
                    'ko' => '공정 조건에 맞춘 바인더 시스템',
                ],
                'content' => 'Purified shell powder is blended with a process-dependent binder system and formed into consistent pellets for downstream moulding.',
                'content_translations' => [
                    'en' => 'Purified shell powder is blended with a process-dependent binder system and formed into consistent pellets for downstream moulding.',
                    'zh' => '净化后的贝壳粉与随工艺调整的粘结体系混合，并制成稳定颗粒用于后续成型。',
                    'ko' => '정제된 쉘 파우더를 공정 조건에 맞춘 바인더 시스템과 혼합해 후속 성형용 균일 펠릿으로 만듭니다.',
                ],
                'highlight' => 'Consistent pellets',
                'highlight_translations' => [
                    'en' => 'Consistent pellets',
                    'zh' => '稳定颗粒',
                    'ko' => '균일 펠릿',
                ],
                'sort_order' => 3,
            ],
            [
                'title' => 'Compression moulding and finishing',
                'title_translations' => [
                    'en' => 'Compression moulding and finishing',
                    'zh' => '压缩成型与表面处理',
                    'ko' => '압축 성형 및 마감',
                ],
                'subtitle' => 'Premium tactile finish',
                'subtitle_translations' => [
                    'en' => 'Premium tactile finish',
                    'zh' => '高端触感表面',
                    'ko' => '프리미엄 촉각 마감',
                ],
                'content' => 'Pellets are compression-moulded into prototype or production-ready objects, then finished for premium visual and tactile quality.',
                'content_translations' => [
                    'en' => 'Pellets are compression-moulded into prototype or production-ready objects, then finished for premium visual and tactile quality.',
                    'zh' => '颗粒通过压缩成型转化为原型或可生产物件，并完成表面处理以呈现高端视觉与触感。',
                    'ko' => '펠릿을 압축 성형해 프로토타입 또는 생산 가능한 오브젝트로 만들고, 프리미엄 시각 및 촉각 품질을 위해 마감합니다.',
                ],
                'highlight' => 'Premium finishing',
                'highlight_translations' => [
                    'en' => 'Premium finishing',
                    'zh' => '高端表面处理',
                    'ko' => '프리미엄 마감',
                ],
                'sort_order' => 4,
            ],
        ];

        foreach ($storySections as $section) {
            MaterialStorySection::query()->updateOrCreate(
                [
                    'material_id' => $material->id,
                    'title' => $section['title'],
                ],
                [
                    'subtitle' => $section['subtitle'],
                    'title_translations' => $section['title_translations'],
                    'subtitle_translations' => $section['subtitle_translations'],
                    'content' => $section['content'],
                    'content_translations' => $section['content_translations'],
                    'highlight' => $section['highlight'],
                    'highlight_translations' => $section['highlight_translations'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $section['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

        MaterialStorySection::query()
            ->where('material_id', $material->id)
            ->whereNotIn('title', array_column($storySections, 'title'))
            ->delete();

        $applications = [
            [
                'title' => 'Hospitality and tabletop',
                'title_translations' => [
                    'en' => 'Hospitality and tabletop',
                    'zh' => '酒店餐饮与桌面物件',
                    'ko' => '호스피탈리티 및 테이블웨어',
                ],
                'subtitle' => 'Premium experiential objects',
                'subtitle_translations' => [
                    'en' => 'Premium experiential objects',
                    'zh' => '高端体验物件',
                    'ko' => '프리미엄 경험 오브젝트',
                ],
                'description' => 'Suitable for trays, service accessories, hospitality display surfaces, and premium experiences where material story matters.',
                'description_translations' => [
                    'en' => 'Suitable for trays, service accessories, hospitality display surfaces, and premium experiences where material story matters.',
                    'zh' => '适用于托盘、服务配件、餐饮空间展示表面，以及强调材料故事的高端体验场景。',
                    'ko' => '트레이, 서비스 액세서리, 호스피탈리티 공간의 디스플레이 표면, 소재 스토리가 중요한 프리미엄 경험에 적합합니다.',
                ],
                'audience' => 'Hotels, restaurants, experience brands',
                'audience_translations' => [
                    'en' => 'Hotels, restaurants, experience brands',
                    'zh' => '酒店、餐厅、体验品牌',
                    'ko' => '호텔, 레스토랑, 경험 브랜드',
                ],
                'cta_label' => 'Discuss hospitality use',
                'cta_label_translations' => [
                    'en' => 'Discuss hospitality use',
                    'zh' => '洽谈酒店餐饮应用',
                    'ko' => '호스피탈리티 활용 문의',
                ],
                'cta_url' => 'https://example.com/collaboration/hospitality',
                'sort_order' => 1,
            ],
            [
                'title' => 'Premium interior objects',
                'title_translations' => [
                    'en' => 'Premium interior objects',
                    'zh' => '高端室内物件',
                    'ko' => '프리미엄 인테리어 오브젝트',
                ],
                'subtitle' => 'Interior accessories and display',
                'subtitle_translations' => [
                    'en' => 'Interior accessories and display',
                    'zh' => '室内配饰与展示',
                    'ko' => '인테리어 액세서리 및 디스플레이',
                ],
                'description' => 'Suitable for decorative objects, tabletop systems, display plinths, and interior accessories with mineral tactility.',
                'description_translations' => [
                    'en' => 'Suitable for decorative objects, tabletop systems, display plinths, and interior accessories with mineral tactility.',
                    'zh' => '适用于装饰摆件、桌面系统、展示基座和具有矿物触感的室内配饰。',
                    'ko' => '장식 오브젝트, 데스크톱 시스템, 디스플레이 플린스, 미네랄 촉감의 인테리어 액세서리에 적합합니다.',
                ],
                'audience' => 'Interior designers, premium brands, studios',
                'audience_translations' => [
                    'en' => 'Interior designers, premium brands, studios',
                    'zh' => '室内设计师、高端品牌、设计工作室',
                    'ko' => '인테리어 디자이너, 프리미엄 브랜드, 스튜디오',
                ],
                'cta_label' => 'Discuss interior objects',
                'cta_label_translations' => [
                    'en' => 'Discuss interior objects',
                    'zh' => '洽谈室内物件',
                    'ko' => '인테리어 오브젝트 문의',
                ],
                'cta_url' => 'https://example.com/collaboration/interior',
                'sort_order' => 2,
            ],
            [
                'title' => 'Retail and brand installations',
                'title_translations' => [
                    'en' => 'Retail and brand installations',
                    'zh' => '零售与品牌陈列',
                    'ko' => '리테일 및 브랜드 설치',
                ],
                'subtitle' => 'Display and merchandising',
                'subtitle_translations' => [
                    'en' => 'Display and merchandising',
                    'zh' => '展示与陈列',
                    'ko' => '디스플레이 및 머천다이징',
                ],
                'description' => 'Supports premium retail displays, brand props, material story zones, and offline experience spaces.',
                'description_translations' => [
                    'en' => 'Supports premium retail displays, brand props, material story zones, and offline experience spaces.',
                    'zh' => '支持高端零售陈列、品牌道具、材料故事展示区和线下体验空间。',
                    'ko' => '프리미엄 리테일 디스플레이, 브랜드 소품, 소재 스토리 존, 오프라인 경험 공간에 활용할 수 있습니다.',
                ],
                'audience' => 'Retail teams, creative agencies, brand studios',
                'audience_translations' => [
                    'en' => 'Retail teams, creative agencies, brand studios',
                    'zh' => '零售团队、创意机构、品牌工作室',
                    'ko' => '리테일 팀, 크리에이티브 에이전시, 브랜드 스튜디오',
                ],
                'cta_label' => 'Discuss retail application',
                'cta_label_translations' => [
                    'en' => 'Discuss retail application',
                    'zh' => '洽谈零售应用',
                    'ko' => '리테일 적용 문의',
                ],
                'cta_url' => 'https://example.com/collaboration/retail',
                'sort_order' => 3,
            ],
            [
                'title' => 'B2B pellet supply and co-development',
                'title_translations' => [
                    'en' => 'B2B pellet supply and co-development',
                    'zh' => 'B2B 颗粒供应与联合开发',
                    'ko' => 'B2B 펠릿 공급 및 공동 개발',
                ],
                'subtitle' => 'Material samples and formulation review',
                'subtitle_translations' => [
                    'en' => 'Material samples and formulation review',
                    'zh' => '材料样品与配方验证',
                    'ko' => '소재 샘플 및 배합 검토',
                ],
                'description' => 'Designed for material samples, pellet supply, formulation validation, co-prototyping, and future product development collaborations.',
                'description_translations' => [
                    'en' => 'Designed for material samples, pellet supply, formulation validation, co-prototyping, and future product development collaborations.',
                    'zh' => '面向材料样品、颗粒供应、配方验证、联合打样和未来产品开发合作。',
                    'ko' => '소재 샘플, 펠릿 공급, 배합 검증, 공동 프로토타이핑, 향후 제품 개발 협업에 적합합니다.',
                ],
                'audience' => 'Manufacturers, material teams, product developers',
                'audience_translations' => [
                    'en' => 'Manufacturers, material teams, product developers',
                    'zh' => '制造商、材料团队、产品开发方',
                    'ko' => '제조사, 소재 팀, 제품 개발자',
                ],
                'cta_label' => 'Discuss pellet supply',
                'cta_label_translations' => [
                    'en' => 'Discuss pellet supply',
                    'zh' => '洽谈颗粒供应',
                    'ko' => '펠릿 공급 문의',
                ],
                'cta_url' => 'https://example.com/collaboration/pellets',
                'sort_order' => 4,
            ],
        ];

        foreach ($applications as $application) {
            MaterialApplication::query()->updateOrCreate(
                [
                    'material_id' => $material->id,
                    'title' => $application['title'],
                ],
                [
                    'subtitle' => $application['subtitle'],
                    'title_translations' => $application['title_translations'],
                    'subtitle_translations' => $application['subtitle_translations'],
                    'description' => $application['description'],
                    'description_translations' => $application['description_translations'],
                    'audience' => $application['audience'],
                    'audience_translations' => $application['audience_translations'],
                    'cta_label' => $application['cta_label'],
                    'cta_label_translations' => $application['cta_label_translations'],
                    'cta_url' => $application['cta_url'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $application['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

        MaterialApplication::query()
            ->where('material_id', $material->id)
            ->whereNotIn('title', array_column($applications, 'title'))
            ->delete();

        $articles = [
            [
                'slug' => 'material-platform-launch',
                'title' => 'Material Platform Launch',
                'title_translations' => [
                    'en' => 'Material Platform Launch',
                    'zh' => '材料平台发布',
                    'ko' => '소재 플랫폼 출시',
                ],
                'excerpt' => 'The oyster-shell material showcase is now structured for premium storytelling, collaboration, and future support flows.',
                'excerpt_translations' => [
                    'en' => 'The oyster-shell material showcase is now structured for premium storytelling, collaboration, and future support flows.',
                    'zh' => '牡蛎壳材料展示现已具备高级叙事、协作和未来支持流程的结构。',
                    'ko' => '굴 껍데기 소재 쇼케이스가 프리미엄 스토리텔링, 협업, 향후 지원 흐름을 담을 수 있는 구조로 정리되었습니다.',
                ],
                'content' => 'This launch establishes the backend foundation for premium material storytelling, editorial updates, and home page content management through the API.',
                'content_translations' => [
                    'en' => 'This launch establishes the backend foundation for premium material storytelling, editorial updates, and home page content management through the API.',
                    'zh' => '此次发布为高级材料叙事、编辑更新和通过 API 管理主页内容奠定了后端基础。',
                    'ko' => '이번 출시는 API를 통한 프리미엄 소재 스토리텔링, 에디토리얼 업데이트, 홈페이지 콘텐츠 관리를 위한 백엔드 기반을 마련합니다.',
                ],
                'category' => 'updates',
                'category_translations' => [
                    'en' => 'updates',
                    'zh' => '更新',
                    'ko' => '업데이트',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'science-notes-shell-composite',
                'title' => 'Science Notes on the Oyster Shell Composite',
                'title_translations' => [
                    'en' => 'Science Notes on the Oyster Shell Composite',
                    'zh' => '牡蛎壳复合材料科学笔记',
                    'ko' => '굴 껍데기 복합소재 과학 노트',
                ],
                'excerpt' => 'A high-level summary of performance, circularity, and positioning signals available for the frontend showcase.',
                'excerpt_translations' => [
                    'en' => 'A high-level summary of performance, circularity, and positioning signals available for the frontend showcase.',
                    'zh' => '前端展示可使用的性能、循环性和定位信号的高层摘要。',
                    'ko' => '프론트엔드 쇼케이스에서 활용할 수 있는 성능, 순환성, 포지셔닝 신호의 요약입니다.',
                ],
                'content' => 'This editorial entry can be used by the frontend to render science-backed context around durability, circularity, and premium application fit.',
                'content_translations' => [
                    'en' => 'This editorial entry can be used by the frontend to render science-backed context around durability, circularity, and premium application fit.',
                    'zh' => '这篇编辑内容可用于前端展示围绕耐用性、循环性和高端应用适配的科学背景。',
                    'ko' => '이 에디토리얼 항목은 내구성, 순환성, 프리미엄 적용 적합성을 둘러싼 과학 기반 맥락을 프론트엔드에 제공하는 데 사용할 수 있습니다.',
                ],
                'category' => 'science',
                'category_translations' => [
                    'en' => 'science',
                    'zh' => '科学',
                    'ko' => '과학',
                ],
                'sort_order' => 2,
            ],
        ];

        foreach ($articles as $article) {
            Article::query()->updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'title' => $article['title'],
                    'title_translations' => $article['title_translations'],
                    'excerpt' => $article['excerpt'],
                    'excerpt_translations' => $article['excerpt_translations'],
                    'content' => $article['content'],
                    'content_translations' => $article['content_translations'],
                    'category' => $article['category'],
                    'category_translations' => $article['category_translations'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $article['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

        $homeSections = [
            [
                'key' => 'hero',
                'title' => 'Premium oyster-shell material for design-led products',
                'subtitle' => 'Material showcase',
                'content' => 'Reclaimed oyster shell, refined into pellets, compress-moulded into premium objects — lighter than porcelain, stronger than ceramic, naturally safe.',
                'cta_label' => 'Explore the material',
                'cta_url' => '/materials/premium-oyster-shell',
                'payload' => [
                    'variant' => 'hero',
                    'theme' => 'premium-shell',
                ],
                'sort_order' => 1,
            ],
            [
                'key' => 'science_block',
                'title' => 'Science-backed qualities and specifications',
                'title_translations' => [
                    'en' => 'Science-backed qualities and specifications',
                    'zh' => '以科学验证的材料特性与规格',
                    'ko' => '과학적 근거를 갖춘 소재 특성과 사양',
                ],
                'subtitle' => 'Material proof points',
                'subtitle_translations' => [
                    'en' => 'Material proof points',
                    'zh' => '材料证明要点',
                    'ko' => '소재 검증 포인트',
                ],
                'content' => 'Frontend sections can render measured qualities, recycled-origin messaging, and application fit from the CMS payload.',
                'content_translations' => [
                    'en' => 'Frontend sections can render measured qualities, recycled-origin messaging, and application fit from the CMS payload.',
                    'zh' => '主页区块可以从 CMS 内容中展示可测量的材料特性、回收来源叙事和应用适配方向。',
                    'ko' => '홈페이지 섹션은 CMS 데이터를 기반으로 측정 가능한 품질, 재활용 원료 메시지, 적용 분야를 보여줄 수 있습니다.',
                ],
                'cta_label' => 'Review specs',
                'cta_label_translations' => [
                    'en' => 'Review specs',
                    'zh' => '查看规格',
                    'ko' => '사양 보기',
                ],
                'cta_url' => '/materials/premium-oyster-shell',
                'payload' => [
                    'variant' => 'science',
                    'material_slug' => 'premium-oyster-shell',
                ],
                'sort_order' => 2,
            ],
            [
                'key' => 'latest_updates',
                'title' => 'Latest news and progress',
                'title_translations' => [
                    'en' => 'Latest news and progress',
                    'zh' => '最新新闻与项目进展',
                    'ko' => '최신 소식과 진행 상황',
                ],
                'subtitle' => 'Articles and updates',
                'subtitle_translations' => [
                    'en' => 'Articles and updates',
                    'zh' => '文章与更新',
                    'ko' => '아티클 및 업데이트',
                ],
                'content' => 'Published articles are exposed through the API and can be surfaced on the homepage dynamically.',
                'content_translations' => [
                    'en' => 'Published articles are exposed through the API and can be surfaced on the homepage dynamically.',
                    'zh' => '已发布的文章会通过 API 输出，并可以动态展示在主页。',
                    'ko' => '게시된 아티클은 API를 통해 제공되며 홈페이지에 동적으로 노출될 수 있습니다.',
                ],
                'cta_label' => 'Read updates',
                'cta_label_translations' => [
                    'en' => 'Read updates',
                    'zh' => '阅读更新',
                    'ko' => '업데이트 읽기',
                ],
                'cta_url' => '/articles',
                'payload' => [
                    'variant' => 'article_feed',
                    'limit' => 3,
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($homeSections as $section) {
            HomeSection::query()->updateOrCreate(
                ['key' => $section['key']],
                [
                    'title' => $section['title'],
                    'title_translations' => $section['title_translations'] ?? [
                        'en' => $section['title'],
                    ],
                    'subtitle' => $section['subtitle'],
                    'subtitle_translations' => $section['subtitle_translations'] ?? [
                        'en' => $section['subtitle'],
                    ],
                    'content' => $section['content'],
                    'content_translations' => $section['content_translations'] ?? [
                        'en' => $section['content'],
                    ],
                    'cta_label' => $section['cta_label'],
                    'cta_label_translations' => $section['cta_label_translations'] ?? [
                        'en' => $section['cta_label'],
                    ],
                    'cta_url' => $section['cta_url'],
                    'payload' => $section['payload'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $section['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }
    }
}
