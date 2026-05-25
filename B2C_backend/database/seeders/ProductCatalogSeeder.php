<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    private const SEED_SOURCE = 'product_catalog_initial';

    private const DEFINITION_LABEL_TRANSLATIONS = [
        'material_family' => ['en' => 'Material Family', 'zh' => '材料系列',  'ko' => '소재 패밀리'],
        'model' => ['en' => 'Model',           'zh' => '型号',      'ko' => '모델'],
        'finish' => ['en' => 'Finish',          'zh' => '表面工艺',  'ko' => '피니시'],
        'color' => ['en' => 'Color',           'zh' => '颜色',      'ko' => '컬러'],
        'technique' => ['en' => 'Technique',       'zh' => '工艺技法',  'ko' => '기법'],
        'use_case' => ['en' => 'Use Case',        'zh' => '用途',      'ko' => '사용 사례'],
        'dimensions' => ['en' => 'Dimensions',      'zh' => '尺寸规格',  'ko' => '치수'],
    ];

    private const VALUE_LABEL_TRANSLATIONS = [
        'oxp' => ['en' => 'OXP',                 'zh' => 'OXP',        'ko' => 'OXP'],
        'heritage_16' => ['en' => 'Heritage 16',         'zh' => 'Heritage 16', 'ko' => 'Heritage 16'],
        'lite_15' => ['en' => 'Lite 15',             'zh' => 'Lite 15',    'ko' => 'Lite 15'],
        'matte' => ['en' => 'Matte',               'zh' => '哑光',       'ko' => '매트'],
        'glossy' => ['en' => 'Glossy',              'zh' => '光泽',       'ko' => '글로시'],
        'ocean_bone' => ['en' => 'Ocean Bone',          'zh' => '海洋骨白',   'ko' => '오션 본'],
        'forged_ash' => ['en' => 'Forged Ash',          'zh' => '锻造灰',     'ko' => '포지드 애쉬'],
        'original_pure' => ['en' => 'Original Pure',       'zh' => '原纯工艺',   'ko' => '오리지널 퓨어'],
        'precision_inlay' => ['en' => 'Precision Inlay',     'zh' => '精准嵌合',   'ko' => '프리시전 인레이'],
        'driftwood_blend' => ['en' => 'Driftwood Blend',     'zh' => '漂木混合',   'ko' => '드리프트우드 블렌드'],
        'home_dining' => ['en' => 'Home Dining',         'zh' => '家居餐饮',   'ko' => '홈 다이닝'],
        'hospitality_service' => ['en' => 'Hospitality Service', 'zh' => '酒店服务',   'ko' => '호스피탈리티 서비스'],
        'retail_gifting' => ['en' => 'Retail Gifting',      'zh' => '零售礼赠',   'ko' => '리테일 선물'],
        'interior_styling' => ['en' => 'Interior Styling',    'zh' => '室内陈设',   'ko' => '인테리어 스타일링'],
        'design_projects' => ['en' => 'Design Projects',     'zh' => '设计项目',   'ko' => '디자인 프로젝트'],
    ];

    public function run(): void
    {
        $this->ensureBaseDefinitions();

        $categories = collect([
            [
                'slug' => 'tableware',
                'name' => 'Tableware',
                'name_translations' => [
                    'en' => 'Tableware',
                    'zh' => '餐具',
                    'ko' => '테이블웨어',
                ],
                'description' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                'description_translations' => [
                    'en' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                    'zh' => '专为酒店餐饮与日常仪式感设计的桌面餐具系列。',
                    'ko' => '호스피탈리티 서비스와 고요한 일상 의례를 위해 만들어진 테이블웨어 컬렉션.',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'planters',
                'name' => 'Planters',
                'name_translations' => [
                    'en' => 'Planters',
                    'zh' => '花器与种植器',
                    'ko' => '플랜터',
                ],
                'description' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                'description_translations' => [
                    'en' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                    'zh' => '适合植物造型与精品室内陈设的花器与雕塑性容器。',
                    'ko' => '보태니컬 스타일링과 프리미엄 인테리어를 위한 플랜터와 조형적 용기 모음.',
                ],
                'sort_order' => 2,
            ],
            [
                'slug' => 'wellness_interior',
                'name' => 'Wellness & Interior',
                'name_translations' => [
                    'en' => 'Wellness & Interior',
                    'zh' => '香氛与室内物件',
                    'ko' => '웰니스 & 인테리어',
                ],
                'description' => 'Interior accents and wellness objects designed around quieter material rituals.',
                'description_translations' => [
                    'en' => 'Interior accents and wellness objects designed around quieter material rituals.',
                    'zh' => '围绕安静材料仪式感打造的室内点缀与香氛物件。',
                    'ko' => '차분한 소재 리추얼을 중심으로 디자인된 인테리어 소품과 웰니스 오브제.',
                ],
                'sort_order' => 3,
            ],
            [
                'slug' => 'architectural',
                'name' => 'Architectural',
                'name_translations' => [
                    'en' => 'Architectural',
                    'zh' => '建筑与设计样品',
                    'ko' => '건축 및 디자인 샘플',
                ],
                'description' => 'Material review pieces and surface objects for design studios and hospitality projects.',
                'description_translations' => [
                    'en' => 'Material review pieces and surface objects for design studios and hospitality projects.',
                    'zh' => '面向设计工作室与酒店项目的材料评估样片与表面物件。',
                    'ko' => '디자인 스튜디오와 호스피탈리티 프로젝트를 위한 소재 검토 피스와 표면 오브제.',
                ],
                'sort_order' => 4,
            ],
        ])->mapWithKeys(function (array $category): array {
            $record = ProductCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    ...$category,
                    'is_active' => true,
                    ...$this->initialMetadata(),
                ],
            );

            return [$category['slug'] => $record];
        });

        $products = [
            // ─────────────────────────────────────────────────────────────────
            // 1. Tidal Dinner Plate
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'tidal-dinner-plate',
                'sku' => 'TIDAL_DINNER_PLATE',
                'category' => 'tableware',

                'name' => 'Tidal Dinner Plate',
                'name_translations' => [
                    'en' => 'Tidal Dinner Plate',
                    'zh' => 'Tidal 晚餐盘',
                    'ko' => 'Tidal 디너 플레이트',
                ],

                'subtitle' => 'Mineral-soft dinner plate designed for premium daily service.',
                'subtitle_translations' => [
                    'en' => 'Mineral-soft dinner plate designed for premium daily service.',
                    'zh' => '为高端日常餐饮设计的矿物质感晚餐盘。',
                    'ko' => '프리미엄 데일리 다이닝을 위해 설계된 미네랄 감성의 디너 플레이트.',
                ],

                'short_description' => 'A refined dinner plate with a mineral-soft edge, quieter weight profile, and strong shell-led tactility.',
                'short_description_translations' => [
                    'en' => 'A refined dinner plate with a mineral-soft edge, quieter weight profile, and strong shell-led tactility.',
                    'zh' => '一款边缘柔和、手感更轻盈的晚餐盘，呈现清晰的牡蛎壳再生材料触感。',
                    'ko' => '부드러운 가장자리와 가벼운 사용감을 갖춘 디너 플레이트로, 재생 굴껍데기 소재의 촉감을 선명하게 전달합니다.',
                ],

                'full_description' => 'Developed for chef-led dining rooms and design-conscious homes that want a premium plate with lighter handling, low absorption, and a clear reclaimed-material narrative. The broad face supports plated courses while the mineral edge keeps the presentation crisp without feeling overly formal.',
                'full_description_translations' => [
                    'en' => 'Developed for chef-led dining rooms and design-conscious homes that want a premium plate with lighter handling, low absorption, and a clear reclaimed-material narrative. The broad face supports plated courses while the mineral edge keeps the presentation crisp without feeling overly formal.',
                    'zh' => '专为主厨餐厅与注重设计的家居空间开发，提供更轻巧的操持感、低吸水性，以及清晰的再生材料叙事。宽幅盘面适合摆盘呈现，矿物质感盘沿在保持精致外观的同时，避免了过于正式的感觉。',
                    'ko' => '주방장이 이끄는 다이닝룸과 디자인 감각 있는 가정을 위해 개발된 제품입니다. 가벼운 핸들링, 낮은 흡수율, 명확한 재생 소재 스토리를 담았습니다. 넓은 면은 플레이팅 코스를 받쳐주며, 미네랄 감성의 가장자리가 지나치게 격식적이지 않으면서도 깔끔한 프레젠테이션을 유지합니다.',
                ],

                'features' => [
                    'Compression-moulded shell composite body',
                    'Balanced weight for long service shifts',
                    'Low-absorption surface for premium dining use',
                ],
                'features_translations' => [
                    'en' => [
                        'Compression-moulded shell composite body',
                        'Balanced weight for long service shifts',
                        'Low-absorption surface for premium dining use',
                    ],
                    'zh' => [
                        '压缩模塑牡蛎壳复合材质',
                        '适合长时间服务班次的均衡重量',
                        '适用于高端餐饮的低吸水表面',
                    ],
                    'ko' => [
                        '압축 성형 쉘 복합 소재 바디',
                        '긴 서비스 시프트를 위한 균형 잡힌 무게',
                        '프리미엄 다이닝을 위한 저흡수 표면',
                    ],
                ],

                'availability_text' => 'Ready from the current production batch',
                'availability_text_translations' => [
                    'en' => 'Ready from the current production batch',
                    'zh' => '当前生产批次可供发货',
                    'ko' => '현재 생산 배치에서 출고 가능',
                ],

                'lead_time' => 'Ships in 2-4 business days',
                'lead_time_translations' => [
                    'en' => 'Ships in 2-4 business days',
                    'zh' => '2–4 个工作日内发货',
                    'ko' => '영업일 기준 2–4일 내 발송',
                ],

                'care_instructions' => [
                    'Dishwasher safe on a gentle cycle.',
                    'Avoid direct stovetop flame or oven use.',
                    'Use soft separators for high-turn hospitality stacking.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Dishwasher safe on a gentle cycle.',
                        'Avoid direct stovetop flame or oven use.',
                        'Use soft separators for high-turn hospitality stacking.',
                    ],
                    'zh' => [
                        '可在温和模式下使用洗碗机清洁。',
                        '避免明火或烤箱直接接触。',
                        '高周转率的酒店叠放建议使用软质隔垫。',
                    ],
                    'ko' => [
                        '부드러운 사이클로 식기세척기 사용 가능.',
                        '직화 또는 오븐 사용 금지.',
                        '고회전 호스피탈리티 스태킹 시 소프트 분리대 사용 권장.',
                    ],
                ],

                'material_benefits' => [
                    'Mineral tactility shaped from reclaimed oyster shell feedstock.',
                    'Lighter handling than many heavy ceramic service plates.',
                    'Premium traceability story for dining programs and gifting.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Mineral tactility shaped from reclaimed oyster shell feedstock.',
                        'Lighter handling than many heavy ceramic service plates.',
                        'Premium traceability story for dining programs and gifting.',
                    ],
                    'zh' => [
                        '由再生牡蛎壳原料塑造的矿物质感。',
                        '比许多重型陶瓷餐具更轻巧。',
                        '为餐饮计划与礼赠提供优质的可追溯材料叙事。',
                    ],
                    'ko' => [
                        '재생 굴껍데기 원료로 만들어진 미네랄 촉감.',
                        '많은 중형 세라믹 서비스 플레이트보다 가벼운 핸들링.',
                        '다이닝 프로그램과 선물용으로 프리미엄 추적성 스토리 제공.',
                    ],
                ],

                'seo_title' => 'Tidal Dinner Plate | OXP premium oyster-shell tableware',
                'seo_title_translations' => [
                    'en' => 'Tidal Dinner Plate | OXP premium oyster-shell tableware',
                    'zh' => 'Tidal 晚餐盘 | OXP 高端牡蛎壳餐具',
                    'ko' => 'Tidal 디너 플레이트 | OXP 프리미엄 굴껍데기 테이블웨어',
                ],

                'seo_description' => 'A premium oyster-shell dinner plate with refined tactility, lighter handling, and hospitality-ready durability.',
                'seo_description_translations' => [
                    'en' => 'A premium oyster-shell dinner plate with refined tactility, lighter handling, and hospitality-ready durability.',
                    'zh' => '一款具有精致触感、更轻操持感与酒店级耐用性的高端牡蛎壳晚餐盘。',
                    'ko' => '세련된 촉감, 가벼운 핸들링, 호스피탈리티 수준의 내구성을 갖춘 프리미엄 굴껍데기 디너 플레이트.',
                ],

                'image_url' => '/images/application-tableware.jpg',
                'gallery' => [
                    '/images/application-tableware.jpg',
                    '/images/hero-material.jpg',
                    '/images/material-texture.jpg',
                ],

                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'dimensions' => 'Dia 27 cm x H 2.4 cm',
                'weight_grams' => 590,

                'attribute_specs' => [
                    [
                        'key' => 'rim_profile',
                        'label' => 'Rim Profile',
                        'label_translations' => ['en' => 'Rim Profile', 'zh' => '盘沿轮廓',       'ko' => '림 프로파일'],
                        'value' => 'Soft coupe edge',
                        'value_translations' => ['en' => 'Soft coupe edge', 'zh' => '柔和 coupe 盘沿', 'ko' => '부드러운 쿠프형 림'],
                        'group' => 'Product',
                    ],
                    [
                        'key' => 'service_pack',
                        'label' => 'Service Pack',
                        'label_translations' => ['en' => 'Service Pack', 'zh' => '服务套装数量', 'ko' => '서비스 팩'],
                        'value' => '12 pcs',
                        'value_translations' => ['en' => '12 pcs', 'zh' => '12 件', 'ko' => '12개'],
                        'group' => 'Program',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['home_dining', 'hospitality_service', 'retail_gifting'],
                'price_from' => 76,
                'currency' => 'NZD',
                'featured' => true,
                'is_bestseller' => true,
                'is_new' => false,
                'sort_order' => 1,
                'compare_at_price_amount' => 92,
                'price_amount' => 76,
                'stock_quantity' => 42,
                'stock_status' => 'in_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'related' => [
                    'harbor-serving-bowl',
                    'salt-air-espresso-set',
                    'studio-review-kit',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 2. Harbor Serving Bowl
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'harbor-serving-bowl',
                'sku' => 'HARBOR_SERVING_BOWL',
                'category' => 'tableware',

                'name' => 'Harbor Serving Bowl',
                'name_translations' => [
                    'en' => 'Harbor Serving Bowl',
                    'zh' => 'Harbor 分享碗',
                    'ko' => 'Harbor 서빙 볼',
                ],

                'subtitle' => 'Generous serving bowl tuned for chef-led tables and boutique stays.',
                'subtitle_translations' => [
                    'en' => 'Generous serving bowl tuned for chef-led tables and boutique stays.',
                    'zh' => '面向主厨餐桌与精品住宿场景的大容量分享碗。',
                    'ko' => '셰프 테이블과 부티크 스테이를 위해 조율된 넉넉한 서빙 볼.',
                ],

                'short_description' => 'A hospitality-scaled serving bowl with a warm mineral profile and a durable rim built for repeat service.',
                'short_description_translations' => [
                    'en' => 'A hospitality-scaled serving bowl with a warm mineral profile and a durable rim built for repeat service.',
                    'zh' => '一款适合酒店场景的分享碗，具有温润矿物质感与耐用盘沿，可胜任反复使用。',
                    'ko' => '따뜻한 미네랄 프로파일과 반복 서비스를 위한 내구성 있는 림을 갖춘 호스피탈리티 규모의 서빙 볼.',
                ],

                'full_description' => 'Sized for shared courses, breakfast service, and premium residential tables, Harbor brings a heavier visual presence without losing the lighter handling benefits of the OXP material system. It is especially suited to boutique hotel breakfast, shared appetizers, and curated chef service packs.',
                'full_description_translations' => [
                    'en' => 'Sized for shared courses, breakfast service, and premium residential tables, Harbor brings a heavier visual presence without losing the lighter handling benefits of the OXP material system. It is especially suited to boutique hotel breakfast, shared appetizers, and curated chef service packs.',
                    'zh' => 'Harbor 专为共享菜肴、早餐服务与高端家居餐桌设计，在不失 OXP 材料系统轻巧操持优势的同时，带来更具视觉分量的呈现。尤其适合精品酒店早餐、共享开胃菜与精选主厨服务套装。',
                    'ko' => '공유 코스, 조식 서비스, 프리미엄 주거용 테이블을 위한 사이즈로, Harbor는 OXP 소재 시스템의 가벼운 핸들링 이점을 유지하면서도 더욱 묵직한 시각적 존재감을 선사합니다. 부티크 호텔 조식, 공유 애피타이저, 큐레이션된 셰프 서비스 팩에 특히 적합합니다.',
                ],

                'features' => [
                    'Durable hospitality-ready rim',
                    'Warm matte finish',
                    'Shared-course proportions',
                ],
                'features_translations' => [
                    'en' => [
                        'Durable hospitality-ready rim',
                        'Warm matte finish',
                        'Shared-course proportions',
                    ],
                    'zh' => [
                        '酒店级耐用盘沿',
                        '温润哑光表面',
                        '适合共享菜肴的宽幅尺寸',
                    ],
                    'ko' => [
                        '호스피탈리티에 적합한 내구성 림',
                        '따뜻한 매트 피니시',
                        '공유 코스 비율',
                    ],
                ],

                'availability_text' => 'Limited batch in stock',
                'availability_text_translations' => [
                    'en' => 'Limited batch in stock',
                    'zh' => '限量批次现货供应',
                    'ko' => '한정 배치 재고 보유 중',
                ],

                'lead_time' => 'Ships in 5-7 business days',
                'lead_time_translations' => [
                    'en' => 'Ships in 5-7 business days',
                    'zh' => '5–7 个工作日内发货',
                    'ko' => '영업일 기준 5–7일 내 발송',
                ],

                'care_instructions' => [
                    'Rinse quickly after acidic sauces or dressings.',
                    'Warm dishwasher cycle recommended.',
                    'Avoid metal scouring pads on the finish.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Rinse quickly after acidic sauces or dressings.',
                        'Warm dishwasher cycle recommended.',
                        'Avoid metal scouring pads on the finish.',
                    ],
                    'zh' => [
                        '使用酸性酱汁或调味料后请尽快冲洗。',
                        '建议使用温和洗碗机模式清洁。',
                        '避免使用金属钢丝球擦拭表面。',
                    ],
                    'ko' => [
                        '산성 소스나 드레싱 후 빠르게 헹구세요.',
                        '따뜻한 식기세척기 사이클 권장.',
                        '피니시에 금속 수세미 사용 금지.',
                    ],
                ],

                'material_benefits' => [
                    'Dense mineral feel with lower water absorption.',
                    'Durable edge performance for repeated service.',
                    'Refined shell story for boutique hospitality programs.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Dense mineral feel with lower water absorption.',
                        'Durable edge performance for repeated service.',
                        'Refined shell story for boutique hospitality programs.',
                    ],
                    'zh' => [
                        '密实矿物质感，低吸水率。',
                        '边缘耐用，适合反复服务使用。',
                        '面向精品酒店项目的精致贝壳叙事。',
                    ],
                    'ko' => [
                        '낮은 흡수율을 갖춘 밀도 있는 미네랄 질감.',
                        '반복 서비스를 위한 내구성 있는 엣지 성능.',
                        '부티크 호스피탈리티 프로그램을 위한 세련된 쉘 스토리.',
                    ],
                ],

                'seo_title' => 'Harbor Serving Bowl | OXP hospitality-ready servingware',
                'seo_title_translations' => [
                    'en' => 'Harbor Serving Bowl | OXP hospitality-ready servingware',
                    'zh' => 'Harbor 分享碗 | OXP 酒店级餐具',
                    'ko' => 'Harbor 서빙 볼 | OXP 호스피탈리티 서빙웨어',
                ],

                'seo_description' => 'A generous OXP serving bowl for chef-led hospitality service and premium residential tables.',
                'seo_description_translations' => [
                    'en' => 'A generous OXP serving bowl for chef-led hospitality service and premium residential tables.',
                    'zh' => '一款宽幅 OXP 分享碗，适合主厨主导的酒店服务与高端家居餐桌。',
                    'ko' => '셰프 중심의 호스피탈리티 서비스와 프리미엄 주거용 테이블을 위한 넉넉한 OXP 서빙 볼.',
                ],

                'image_url' => '/images/application-interior.jpg',
                'gallery' => [
                    '/images/application-interior.jpg',
                    '/images/material-texture.jpg',
                    '/images/application-tableware.jpg',
                ],

                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'precision_inlay',
                'dimensions' => 'Dia 21 cm x H 7.5 cm',
                'weight_grams' => 720,

                'attribute_specs' => [
                    [
                        'key' => 'capacity',
                        'label' => 'Capacity',
                        'label_translations' => ['en' => 'Capacity', 'zh' => '容积', 'ko' => '용량'],
                        'value' => '1.3',
                        'value_translations' => ['en' => '1.3', 'zh' => '1.3', 'ko' => '1.3'],
                        'unit' => 'L',
                        'group' => 'Dimensions',
                    ],
                    [
                        'key' => 'service_pack',
                        'label' => 'Service Pack',
                        'label_translations' => ['en' => 'Service Pack', 'zh' => '服务套装数量', 'ko' => '서비스 팩'],
                        'value' => '8 pcs',
                        'value_translations' => ['en' => '8 pcs', 'zh' => '8 件', 'ko' => '8개'],
                        'group' => 'Program',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['hospitality_service', 'home_dining'],
                'price_from' => 96,
                'currency' => 'NZD',
                'featured' => true,
                'is_bestseller' => false,
                'is_new' => false,
                'sort_order' => 2,
                'compare_at_price_amount' => 118,
                'price_amount' => 96,
                'stock_quantity' => 5,
                'stock_status' => 'low_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'related' => [
                    'tidal-dinner-plate',
                    'salt-air-espresso-set',
                    'cove-display-tile',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 3. Salt Air Espresso Set
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'salt-air-espresso-set',
                'sku' => 'SALT_AIR_ESPRESSO_SET',
                'category' => 'tableware',

                'name' => 'Salt Air Espresso Set',
                'name_translations' => [
                    'en' => 'Salt Air Espresso Set',
                    'zh' => 'Salt Air 浓缩咖啡套组',
                    'ko' => 'Salt Air 에스프레소 세트',
                ],

                'subtitle' => 'Compact cup and saucer pairing built for gifting and premium coffee rituals.',
                'subtitle_translations' => [
                    'en' => 'Compact cup and saucer pairing built for gifting and premium coffee rituals.',
                    'zh' => '适合礼赠与精品咖啡仪式的杯碟组合。',
                    'ko' => '선물과 프리미엄 커피 리추얼을 위한 컴팩트한 컵과 소서 구성.',
                ],

                'short_description' => 'A smaller-shell tableware set for morning service, gifting programs, and boutique retail moments.',
                'short_description_translations' => [
                    'en' => 'A smaller-shell tableware set for morning service, gifting programs, and boutique retail moments.',
                    'zh' => '一款精巧的贝壳材质餐具套组，适合早晨服务、礼赠计划与精品零售场景。',
                    'ko' => '아침 서비스, 선물 프로그램, 부티크 리테일 순간을 위한 소형 쉘 테이블웨어 세트.',
                ],

                'full_description' => 'Salt Air was designed as an approachable entry into the OXP tableware world: compact enough for retail gifting, refined enough for boutique cafe counters, and distinctive enough to carry the oyster-shell material story into a smaller ritual object.',
                'full_description_translations' => [
                    'en' => 'Salt Air was designed as an approachable entry into the OXP tableware world: compact enough for retail gifting, refined enough for boutique cafe counters, and distinctive enough to carry the oyster-shell material story into a smaller ritual object.',
                    'zh' => 'Salt Air 旨在成为进入 OXP 餐具世界的亲切入口：紧凑到足以用于零售礼赠，精致到足以出现在精品咖啡吧台，同时又足够独特，能将牡蛎壳材料叙事延伸到更小巧的仪式物件之中。',
                    'ko' => 'Salt Air는 OXP 테이블웨어 세계로의 친근한 진입점으로 설계되었습니다. 리테일 선물용으로 충분히 컴팩트하고, 부티크 카페 카운터에 어울릴 만큼 정제되어 있으며, 굴껍데기 소재 스토리를 더 작은 의례 오브제로 전달할 만큼 독특합니다.',
                ],

                'features' => [
                    'Cup and saucer pairing',
                    'Retail-ready gifting proposition',
                    'Cafe counter presentation appeal',
                ],
                'features_translations' => [
                    'en' => [
                        'Cup and saucer pairing',
                        'Retail-ready gifting proposition',
                        'Cafe counter presentation appeal',
                    ],
                    'zh' => [
                        '杯与碟的精配组合',
                        '零售级礼赠提案',
                        '咖啡吧台展示美感',
                    ],
                    'ko' => [
                        '컵과 소서 페어링',
                        '리테일 선물 제안',
                        '카페 카운터 프레젠테이션 매력',
                    ],
                ],

                'availability_text' => 'Pre-order now for the next micro batch',
                'availability_text_translations' => [
                    'en' => 'Pre-order now for the next micro batch',
                    'zh' => '现已开放下一批次预订',
                    'ko' => '다음 마이크로 배치 사전 주문 가능',
                ],

                'lead_time' => 'Dispatches in 3-4 weeks',
                'lead_time_translations' => [
                    'en' => 'Dispatches in 3-4 weeks',
                    'zh' => '3–4 周内发货',
                    'ko' => '3–4주 내 발송',
                ],

                'care_instructions' => [
                    'Hand wash recommended for glossy surface retention.',
                    'Do not microwave until production certification is finalized.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Hand wash recommended for glossy surface retention.',
                        'Do not microwave until production certification is finalized.',
                    ],
                    'zh' => [
                        '建议手洗以保持光泽面效果。',
                        '在生产认证完成前，请勿放入微波炉。',
                    ],
                    'ko' => [
                        '광택 표면 유지를 위해 손세척 권장.',
                        '생산 인증이 완료될 때까지 전자레인지 사용 금지.',
                    ],
                ],

                'material_benefits' => [
                    'Compact gifting format with a clear shell-origin material story.',
                    'Low-absorption surface for coffee service and boutique retail display.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Compact gifting format with a clear shell-origin material story.',
                        'Low-absorption surface for coffee service and boutique retail display.',
                    ],
                    'zh' => [
                        '精巧礼赠尺寸，携带清晰的贝壳原产材料叙事。',
                        '低吸水表面，适合咖啡服务与精品零售展示。',
                    ],
                    'ko' => [
                        '명확한 쉘 원산 소재 스토리를 담은 컴팩트 선물 포맷.',
                        '커피 서비스와 부티크 리테일 디스플레이에 적합한 저흡수 표면.',
                    ],
                ],

                'seo_title' => 'Salt Air Espresso Set | OXP oyster-shell giftable tableware',
                'seo_title_translations' => [
                    'en' => 'Salt Air Espresso Set | OXP oyster-shell giftable tableware',
                    'zh' => 'Salt Air 浓缩咖啡套组 | OXP 牡蛎壳可礼赠餐具',
                    'ko' => 'Salt Air 에스프레소 세트 | OXP 굴껍데기 선물용 테이블웨어',
                ],

                'seo_description' => 'A compact OXP espresso set designed for premium rituals, gifting, and boutique hospitality.',
                'seo_description_translations' => [
                    'en' => 'A compact OXP espresso set designed for premium rituals, gifting, and boutique hospitality.',
                    'zh' => '一款精致的 OXP 浓缩咖啡套组，专为高端仪式感、礼赠与精品酒店体验设计。',
                    'ko' => '프리미엄 리추얼, 선물, 부티크 호스피탈리티를 위해 설계된 컴팩트한 OXP 에스프레소 세트.',
                ],

                'image_url' => '/images/application-retail.jpg',
                'gallery' => [
                    '/images/application-retail.jpg',
                    '/images/material-texture.jpg',
                    '/images/hero-material.jpg',
                ],

                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'driftwood_blend',
                'dimensions' => 'Cup Dia 7 cm x H 6 cm / Saucer Dia 12 cm',
                'weight_grams' => 310,

                'attribute_specs' => [
                    [
                        'key' => 'set_contents',
                        'label' => 'Set Contents',
                        'label_translations' => ['en' => 'Set Contents', 'zh' => '套组内容', 'ko' => '세트 구성'],
                        'value' => 'Cup + Saucer',
                        'value_translations' => ['en' => 'Cup + Saucer', 'zh' => '杯 + 碟', 'ko' => '컵 + 소서'],
                        'group' => 'Product',
                    ],
                    [
                        'key' => 'capacity',
                        'label' => 'Cup Capacity',
                        'label_translations' => ['en' => 'Cup Capacity', 'zh' => '杯容量', 'ko' => '컵 용량'],
                        'value' => '150',
                        'value_translations' => ['en' => '150', 'zh' => '150', 'ko' => '150'],
                        'unit' => 'ml',
                        'group' => 'Dimensions',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['home_dining', 'retail_gifting', 'hospitality_service'],
                'price_from' => 58,
                'currency' => 'NZD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'sort_order' => 3,
                'compare_at_price_amount' => 68,
                'price_amount' => 58,
                'stock_quantity' => null,
                'stock_status' => 'preorder',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'related' => [
                    'tidal-dinner-plate',
                    'harbor-serving-bowl',
                    'shoreline-wellness-tray',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 4. Drift Planter No. 2
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'drift-planter-no-2',
                'sku' => 'DRIFT_PLANTER_NO_2',
                'category' => 'planters',

                'name' => 'Drift Planter No. 2',
                'name_translations' => [
                    'en' => 'Drift Planter No. 2',
                    'zh' => 'Drift 种植器 No. 2',
                    'ko' => 'Drift 플랜터 No. 2',
                ],

                'subtitle' => 'Mineral planter with a soft silhouette for residential and hospitality styling.',
                'subtitle_translations' => [
                    'en' => 'Mineral planter with a soft silhouette for residential and hospitality styling.',
                    'zh' => '适合住宅与酒店陈设的柔和轮廓矿物花器。',
                    'ko' => '주거 공간과 호스피탈리티 스타일링을 위한 부드러운 실루엣의 미네랄 플랜터.',
                ],

                'short_description' => 'A sculptural planter built for design-led homes, boutique hospitality, and visual merchandising.',
                'short_description_translations' => [
                    'en' => 'A sculptural planter built for design-led homes, boutique hospitality, and visual merchandising.',
                    'zh' => '一款适合设计感家居、精品酒店与视觉陈列的雕塑性花器。',
                    'ko' => '디자인 중심의 가정, 부티크 호스피탈리티, 시각적 머천다이징을 위해 제작된 조형적 플랜터.',
                ],

                'full_description' => 'Drift Planter translates OXP materiality into the interior category, giving the oyster-shell story a calmer architectural presence. The piece works equally well for boutique hotel room styling, elevated plant gifting, and retail display capsules.',
                'full_description_translations' => [
                    'en' => 'Drift Planter translates OXP materiality into the interior category, giving the oyster-shell story a calmer architectural presence. The piece works equally well for boutique hotel room styling, elevated plant gifting, and retail display capsules.',
                    'zh' => 'Drift 种植器将 OXP 材质语言延伸至室内品类，赋予牡蛎壳故事一种更平静的建筑气质。这款作品同样适合精品酒店客房陈设、高端植物礼赠，以及零售展示胶囊。',
                    'ko' => 'Drift 플랜터는 OXP 소재성을 인테리어 카테고리로 확장하여 굴껍데기 스토리에 더 차분한 건축적 존재감을 부여합니다. 부티크 호텔 객실 스타일링, 프리미엄 식물 선물, 리테일 디스플레이 캡슐에 동등하게 적합합니다.',
                ],

                'features' => [
                    'Drainage-ready interior shape',
                    'Styling-led sculptural silhouette',
                    'Boutique hospitality placement',
                ],
                'features_translations' => [
                    'en' => [
                        'Drainage-ready interior shape',
                        'Styling-led sculptural silhouette',
                        'Boutique hospitality placement',
                    ],
                    'zh' => [
                        '便于排水的内部形态',
                        '以造型为导向的雕塑轮廓',
                        '适合精品酒店陈设',
                    ],
                    'ko' => [
                        '배수에 적합한 내부 형태',
                        '스타일링 중심의 조형적 실루엣',
                        '부티크 호스피탈리티 배치',
                    ],
                ],

                'availability_text' => 'Available for immediate dispatch',
                'availability_text_translations' => [
                    'en' => 'Available for immediate dispatch',
                    'zh' => '可立即发货',
                    'ko' => '즉시 발송 가능',
                ],

                'lead_time' => 'Ships in 3-5 business days',
                'lead_time_translations' => [
                    'en' => 'Ships in 3-5 business days',
                    'zh' => '3–5 个工作日内发货',
                    'ko' => '영업일 기준 3–5일 내 발송',
                ],

                'care_instructions' => [
                    'Use an insert or drainage layer for live plants.',
                    'Wipe exterior with a soft cloth after watering.',
                    'Suitable for indoor use and covered styling zones.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Use an insert or drainage layer for live plants.',
                        'Wipe exterior with a soft cloth after watering.',
                        'Suitable for indoor use and covered styling zones.',
                    ],
                    'zh' => [
                        '活植物请使用内胆或排水层。',
                        '浇水后用软布擦拭外壁。',
                        '适合室内使用及有遮盖的陈设区域。',
                    ],
                    'ko' => [
                        '살아있는 식물에는 인서트 또는 배수층 사용.',
                        '물 준 후 부드러운 천으로 외부 닦기.',
                        '실내 및 덮인 스타일링 구역에 적합.',
                    ],
                ],

                'material_benefits' => [
                    'Extends the OXP material story beyond the table into interior rituals.',
                    'Lower-absorption surface than many porous indoor decorative materials.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Extends the OXP material story beyond the table into interior rituals.',
                        'Lower-absorption surface than many porous indoor decorative materials.',
                    ],
                    'zh' => [
                        '将 OXP 材料叙事从餐桌延伸至室内仪式。',
                        '比许多多孔室内装饰材料更低的吸水表面。',
                    ],
                    'ko' => [
                        '테이블을 넘어 인테리어 리추얼로 OXP 소재 스토리 확장.',
                        '많은 다공성 실내 장식 소재보다 낮은 흡수 표면.',
                    ],
                ],

                'seo_title' => 'Drift Planter No. 2 | OXP premium interior planter',
                'seo_title_translations' => [
                    'en' => 'Drift Planter No. 2 | OXP premium interior planter',
                    'zh' => 'Drift 种植器 No. 2 | OXP 高端室内花器',
                    'ko' => 'Drift 플랜터 No. 2 | OXP 프리미엄 인테리어 플랜터',
                ],

                'seo_description' => 'A sculptural OXP planter for premium interiors, boutique hospitality, and gifting programs.',
                'seo_description_translations' => [
                    'en' => 'A sculptural OXP planter for premium interiors, boutique hospitality, and gifting programs.',
                    'zh' => '一款适合高端室内陈设、精品酒店与礼赠项目的 OXP 雕塑性花器。',
                    'ko' => '프리미엄 인테리어, 부티크 호스피탈리티, 선물 프로그램을 위한 조형적 OXP 플랜터.',
                ],

                'image_url' => '/images/application-interior.jpg',
                'gallery' => [
                    '/images/application-interior.jpg',
                    '/images/application-packaging.jpg',
                    '/images/material-texture.jpg',
                ],

                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'original_pure',
                'dimensions' => 'Dia 18 cm x H 16 cm',
                'weight_grams' => 860,

                'attribute_specs' => [
                    [
                        'key' => 'opening',
                        'label' => 'Opening',
                        'label_translations' => ['en' => 'Opening', 'zh' => '开口尺寸', 'ko' => '개구부'],
                        'value' => '14',
                        'value_translations' => ['en' => '14', 'zh' => '14', 'ko' => '14'],
                        'unit' => 'cm',
                        'group' => 'Dimensions',
                    ],
                    [
                        'key' => 'liner',
                        'label' => 'Recommended Liner',
                        'label_translations' => ['en' => 'Recommended Liner', 'zh' => '建议内衬', 'ko' => '권장 라이너'],
                        'value' => 'Soft nursery pot insert',
                        'value_translations' => ['en' => 'Soft nursery pot insert', 'zh' => '软质育苗盆内衬', 'ko' => '소프트 화분 인서트'],
                        'group' => 'Care',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['interior_styling', 'retail_gifting', 'design_projects'],
                'price_from' => 88,
                'currency' => 'NZD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'sort_order' => 4,
                'compare_at_price_amount' => 104,
                'price_amount' => 88,
                'stock_quantity' => 18,
                'stock_status' => 'in_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => false,
                'related' => [
                    'shoreline-wellness-tray',
                    'reef-candle-vessel',
                    'cove-display-tile',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 5. Shoreline Wellness Tray
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'shoreline-wellness-tray',
                'sku' => 'SHORELINE_WELLNESS_TRAY',
                'category' => 'wellness_interior',

                'name' => 'Shoreline Wellness Tray',
                'name_translations' => [
                    'en' => 'Shoreline Wellness Tray',
                    'zh' => 'Shoreline 香氛托盘',
                    'ko' => 'Shoreline 웰니스 트레이',
                ],

                'subtitle' => 'Slim tray for scent rituals, bathroom styling, and calm hospitality moments.',
                'subtitle_translations' => [
                    'en' => 'Slim tray for scent rituals, bathroom styling, and calm hospitality moments.',
                    'zh' => '适合香氛、浴室陈设与静谧酒店场景的纤薄托盘。',
                    'ko' => '향, 욕실 스타일링, 차분한 호스피탈리티 순간을 위한 슬림 트레이.',
                ],

                'short_description' => 'A low-profile tray that brings OXP tactility into quieter bathroom, spa, and bedside routines.',
                'short_description_translations' => [
                    'en' => 'A low-profile tray that brings OXP tactility into quieter bathroom, spa, and bedside routines.',
                    'zh' => '一款低调托盘，将 OXP 触感融入更静谧的浴室、水疗与床头日常仪式。',
                    'ko' => 'OXP 촉감을 더 차분한 욕실, 스파, 침대 옆 루틴으로 가져오는 낮은 프로파일 트레이.',
                ],

                'full_description' => 'Shoreline is shaped for settings where objects are seen and handled at close range: boutique bathrooms, spa amenities, bedside styling, and premium gifting. The compact footprint makes it easy to pair with candles, soap, jewelry, or curated guest-room items.',
                'full_description_translations' => [
                    'en' => 'Shoreline is shaped for settings where objects are seen and handled at close range: boutique bathrooms, spa amenities, bedside styling, and premium gifting. The compact footprint makes it easy to pair with candles, soap, jewelry, or curated guest-room items.',
                    'zh' => 'Shoreline 专为近距离观看与触摸的场景设计：精品浴室、水疗设施、床头陈设与高端礼赠。紧凑的占地面积便于与蜡烛、香皂、首饰或精选客房物品搭配使用。',
                    'ko' => 'Shoreline은 가까이서 보고 다루는 환경을 위해 설계되었습니다. 부티크 욕실, 스파 어메니티, 침대 옆 스타일링, 프리미엄 선물. 컴팩트한 면적은 캔들, 비누, 주얼리, 또는 큐레이션된 객실 아이템과 쉽게 매칭됩니다.',
                ],

                'features' => [
                    'Quiet mineral surface',
                    'Spa and bathroom styling appeal',
                    'Gift-ready proportion',
                ],
                'features_translations' => [
                    'en' => [
                        'Quiet mineral surface',
                        'Spa and bathroom styling appeal',
                        'Gift-ready proportion',
                    ],
                    'zh' => [
                        '静谧矿物表面',
                        '水疗与浴室陈设美感',
                        '适合礼赠的尺寸比例',
                    ],
                    'ko' => [
                        '고요한 미네랄 표면',
                        '스파와 욕실 스타일링 매력',
                        '선물에 적합한 비율',
                    ],
                ],

                'availability_text' => 'Available now for direct B2C purchase',
                'availability_text_translations' => [
                    'en' => 'Available now for direct B2C purchase',
                    'zh' => '现已开放直接购买',
                    'ko' => '현재 직접 구매 가능',
                ],

                'lead_time' => 'Ships in 2-4 business days',
                'lead_time_translations' => [
                    'en' => 'Ships in 2-4 business days',
                    'zh' => '2–4 个工作日内发货',
                    'ko' => '영업일 기준 2–4일 내 발송',
                ],

                'care_instructions' => [
                    'Wipe dry after oils or fragrance spills.',
                    'Use a soft cloth to preserve finish clarity.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Wipe dry after oils or fragrance spills.',
                        'Use a soft cloth to preserve finish clarity.',
                    ],
                    'zh' => [
                        '使用油类或香氛后请立即擦干。',
                        '使用软布清洁以保持光泽度。',
                    ],
                    'ko' => [
                        '오일이나 향 흘림 후 건식 닦기.',
                        '피니시 선명도 유지를 위해 부드러운 천 사용.',
                    ],
                ],

                'material_benefits' => [
                    'Brings the oyster-shell story into intimate styling rituals.',
                    'Low-absorption surface works well for soaps, candles, and amenity objects.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Brings the oyster-shell story into intimate styling rituals.',
                        'Low-absorption surface works well for soaps, candles, and amenity objects.',
                    ],
                    'zh' => [
                        '将牡蛎壳故事融入亲密的日常陈设仪式。',
                        '低吸水表面适合香皂、蜡烛与设施摆件。',
                    ],
                    'ko' => [
                        '굴껍데기 스토리를 친밀한 스타일링 리추얼에 접목.',
                        '비누, 캔들, 어메니티 오브제에 적합한 저흡수 표면.',
                    ],
                ],

                'seo_title' => 'Shoreline Wellness Tray | OXP premium interior tray',
                'seo_title_translations' => [
                    'en' => 'Shoreline Wellness Tray | OXP premium interior tray',
                    'zh' => 'Shoreline 香氛托盘 | OXP 高端室内托盘',
                    'ko' => 'Shoreline 웰니스 트레이 | OXP 프리미엄 인테리어 트레이',
                ],

                'seo_description' => 'A compact OXP tray for spa rituals, gifting, and calm interior styling.',
                'seo_description_translations' => [
                    'en' => 'A compact OXP tray for spa rituals, gifting, and calm interior styling.',
                    'zh' => '一款适合水疗仪式、礼赠与安静室内陈设的 OXP 紧凑型托盘。',
                    'ko' => '스파 리추얼, 선물, 차분한 인테리어 스타일링을 위한 컴팩트한 OXP 트레이.',
                ],

                'image_url' => '/images/material-texture.jpg',
                'gallery' => [
                    '/images/material-texture.jpg',
                    '/images/application-packaging.jpg',
                    '/images/application-interior.jpg',
                ],

                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'dimensions' => 'W 23 cm x D 12 cm x H 1.8 cm',
                'weight_grams' => 340,

                'attribute_specs' => [
                    [
                        'key' => 'edge',
                        'label' => 'Edge Detail',
                        'label_translations' => ['en' => 'Edge Detail', 'zh' => '边缘细节', 'ko' => '엣지 디테일'],
                        'value' => 'Soft chamfer',
                        'value_translations' => ['en' => 'Soft chamfer', 'zh' => '柔和倒角', 'ko' => '부드러운 모따기'],
                        'group' => 'Product',
                    ],
                    [
                        'key' => 'placement',
                        'label' => 'Suggested Placement',
                        'label_translations' => ['en' => 'Suggested Placement', 'zh' => '建议摆放位置', 'ko' => '추천 배치'],
                        'value' => 'Bath, vanity, bedside',
                        'value_translations' => ['en' => 'Bath, vanity, bedside', 'zh' => '浴室、梳妆台、床头', 'ko' => '욕실, 세면대, 침대 옆'],
                        'group' => 'Application',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['interior_styling', 'retail_gifting', 'home_dining'],
                'price_from' => 64,
                'currency' => 'NZD',
                'featured' => false,
                'is_bestseller' => true,
                'is_new' => false,
                'sort_order' => 5,
                'compare_at_price_amount' => 79,
                'price_amount' => 64,
                'stock_quantity' => 16,
                'stock_status' => 'in_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => false,
                'related' => [
                    'drift-planter-no-2',
                    'reef-candle-vessel',
                    'salt-air-espresso-set',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 6. Cove Display Tile
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'cove-display-tile',
                'sku' => 'COVE_DISPLAY_TILE',
                'category' => 'architectural',

                'name' => 'Cove Display Tile',
                'name_translations' => [
                    'en' => 'Cove Display Tile',
                    'zh' => 'Cove 展示样片',
                    'ko' => 'Cove 디스플레이 타일',
                ],

                'subtitle' => 'Architectural shell composite review tile for design libraries and hospitality fit-outs.',
                'subtitle_translations' => [
                    'en' => 'Architectural shell composite review tile for design libraries and hospitality fit-outs.',
                    'zh' => '面向设计资料库与酒店项目的 OXP 材料评估样片。',
                    'ko' => '디자인 라이브러리와 호스피탈리티 프로젝트를 위한 OXP 소재 검토 타일.',
                ],

                'short_description' => 'A specification-oriented review piece for interior teams evaluating OXP for hospitality and retail surfaces.',
                'short_description_translations' => [
                    'en' => 'A specification-oriented review piece for interior teams evaluating OXP for hospitality and retail surfaces.',
                    'zh' => '一款面向室内设计团队评估 OXP 用于酒店与零售表面的规格导向评审样件。',
                    'ko' => '호스피탈리티와 리테일 표면에 OXP를 평가하는 인테리어 팀을 위한 스펙 중심의 검토 피스.',
                ],

                'full_description' => 'Cove Display Tile is less a conventional B2C object and more a bridge into future B2B surface programs. It gives designers and hospitality buyers a clear way to review finish, tone, density, and care requirements before moving into larger material conversations.',
                'full_description_translations' => [
                    'en' => 'Cove Display Tile is less a conventional B2C object and more a bridge into future B2B surface programs. It gives designers and hospitality buyers a clear way to review finish, tone, density, and care requirements before moving into larger material conversations.',
                    'zh' => 'Cove 展示样片并非传统意义上的 B2C 商品，而是连接未来 B2B 表面项目的桥梁。它让设计师与酒店采购方能够直观地评估饰面、色调、密度与护理要求，再进入更深入的材料洽谈。',
                    'ko' => 'Cove 디스플레이 타일은 일반적인 B2C 제품이라기보다 미래 B2B 표면 프로그램으로 이어지는 브리지입니다. 설계자와 호스피탈리티 바이어가 더 큰 소재 대화로 넘어가기 전에 피니시, 톤, 밀도, 케어 요건을 명확하게 검토할 수 있게 해줍니다.',
                ],

                'features' => [
                    'Architectural review format',
                    'Finish and density review tool',
                    'Hospitality specification bridge',
                ],
                'features_translations' => [
                    'en' => [
                        'Architectural review format',
                        'Finish and density review tool',
                        'Hospitality specification bridge',
                    ],
                    'zh' => [
                        '建筑级评审格式',
                        '饰面与密度评估工具',
                        '酒店规格洽谈桥梁',
                    ],
                    'ko' => [
                        '건축적 검토 포맷',
                        '피니시와 밀도 검토 도구',
                        '호스피탈리티 사양 브리지',
                    ],
                ],

                'availability_text' => 'Bulk and project enquiry recommended',
                'availability_text_translations' => [
                    'en' => 'Bulk and project enquiry recommended',
                    'zh' => '建议批量采购或项目洽询',
                    'ko' => '대량 구매 및 프로젝트 문의 권장',
                ],

                'lead_time' => 'Review packs ship in 2-3 weeks',
                'lead_time_translations' => [
                    'en' => 'Review packs ship in 2-3 weeks',
                    'zh' => '评估套装 2–3 周内发货',
                    'ko' => '검토 팩 2–3주 내 발송',
                ],

                'care_instructions' => [
                    'Use as a review piece for finish and tone comparison.',
                    'Request project guidance before specifying in wet areas.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Use as a review piece for finish and tone comparison.',
                        'Request project guidance before specifying in wet areas.',
                    ],
                    'zh' => [
                        '作为评估样件使用，用于饰面与色调对比。',
                        '在湿区规格使用前请咨询 OXP 项目指导。',
                    ],
                    'ko' => [
                        '피니시와 톤 비교를 위한 검토 피스로 사용.',
                        '습식 구역 스펙 적용 전 프로젝트 지침 요청.',
                    ],
                ],

                'material_benefits' => [
                    'Carries the reclaimed shell narrative into material library conversations.',
                    'Useful for hospitality teams reviewing premium sustainable finish directions.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Carries the reclaimed shell narrative into material library conversations.',
                        'Useful for hospitality teams reviewing premium sustainable finish directions.',
                    ],
                    'zh' => [
                        '将再生贝壳叙事引入材料库洽谈。',
                        '适合酒店团队评估高端可持续饰面方向。',
                    ],
                    'ko' => [
                        '재생 쉘 내러티브를 소재 라이브러리 대화로 연결.',
                        '프리미엄 지속가능 피니시 방향을 검토하는 호스피탈리티 팀에 유용.',
                    ],
                ],

                'seo_title' => 'Cove Display Tile | OXP architectural material review tile',
                'seo_title_translations' => [
                    'en' => 'Cove Display Tile | OXP architectural material review tile',
                    'zh' => 'Cove 展示样片 | OXP 建筑材料评估样片',
                    'ko' => 'Cove 디스플레이 타일 | OXP 건축 소재 검토 타일',
                ],

                'seo_description' => 'An OXP architectural review tile for hospitality, retail, and interior design project review.',
                'seo_description_translations' => [
                    'en' => 'An OXP architectural review tile for hospitality, retail, and interior design project review.',
                    'zh' => '一款用于酒店、零售与室内设计项目评估的 OXP 建筑级评审样片。',
                    'ko' => '호스피탈리티, 리테일, 인테리어 디자인 프로젝트 검토를 위한 OXP 건축 검토 타일.',
                ],

                'image_url' => '/images/application-packaging.jpg',
                'gallery' => [
                    '/images/application-packaging.jpg',
                    '/images/hero-material.jpg',
                    '/images/process-refined.jpg',
                ],

                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'precision_inlay',
                'dimensions' => 'W 10 cm x D 10 cm x H 0.8 cm',
                'weight_grams' => 180,

                'attribute_specs' => [
                    [
                        'key' => 'review_format',
                        'label' => 'Review Format',
                        'label_translations' => ['en' => 'Review Format', 'zh' => '评审格式', 'ko' => '검토 포맷'],
                        'value' => 'Architectural swatch tile',
                        'value_translations' => ['en' => 'Architectural swatch tile', 'zh' => '建筑样片', 'ko' => '건축 스와치 타일'],
                        'group' => 'Program',
                    ],
                    [
                        'key' => 'moq',
                        'label' => 'Project MOQ',
                        'label_translations' => ['en' => 'Project MOQ', 'zh' => '项目最小订量', 'ko' => '프로젝트 MOQ'],
                        'value' => 'Discuss with OXP team',
                        'value_translations' => ['en' => 'Discuss with OXP team', 'zh' => '请咨询 OXP 团队', 'ko' => 'OXP 팀과 협의'],
                        'group' => 'Commercial',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['design_projects', 'hospitality_service', 'interior_styling'],
                'price_from' => 24,
                'currency' => 'NZD',
                'featured' => true,
                'is_bestseller' => false,
                'is_new' => false,
                'sort_order' => 6,
                'compare_at_price_amount' => null,
                'price_amount' => 24,
                'stock_quantity' => null,
                'stock_status' => 'made_to_order',
                'in_stock' => true,
                'inquiry_only' => true,
                'sample_request_enabled' => true,
                'related' => [
                    'studio-review-kit',
                    'drift-planter-no-2',
                    'harbor-serving-bowl',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 7. Studio Review Kit
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'studio-review-kit',
                'sku' => 'STUDIO_REVIEW_KIT',
                'category' => 'architectural',

                'name' => 'Studio Review Kit',
                'name_translations' => [
                    'en' => 'Studio Review Kit',
                    'zh' => 'Studio 评估套件',
                    'ko' => 'Studio 리뷰 키트',
                ],

                'subtitle' => 'Entry review kit for designers, specifiers, and hospitality buyers.',
                'subtitle_translations' => [
                    'en' => 'Entry review kit for designers, specifiers, and hospitality buyers.',
                    'zh' => '面向设计师、规格制定者与酒店采购方的入门评估套件。',
                    'ko' => '디자이너, 스펙 담당자, 호스피탈리티 바이어를 위한 입문용 리뷰 키트.',
                ],

                'short_description' => 'A compact pack of OXP finish chips and object references for teams reviewing the material story.',
                'short_description_translations' => [
                    'en' => 'A compact pack of OXP finish chips and object references for teams reviewing the material story.',
                    'zh' => '一套紧凑的 OXP 饰面样片与实物参考，供评审材料故事的团队使用。',
                    'ko' => '소재 스토리를 검토하는 팀을 위한 OXP 피니시 칩과 오브제 레퍼런스가 담긴 컴팩트 팩.',
                ],

                'full_description' => 'The Studio Review Kit is intended for early project conversations. It combines finish references, a small-format object, and care notes so design teams can move from inspiration to a more grounded materials review without committing to a large order.',
                'full_description_translations' => [
                    'en' => 'The Studio Review Kit is intended for early project conversations. It combines finish references, a small-format object, and care notes so design teams can move from inspiration to a more grounded materials review without committing to a large order.',
                    'zh' => 'Studio 评估套件专为早期项目洽谈而设计，结合饰面参考、小型实物与护理说明，帮助设计团队在不承诺大量订单的情况下，从灵感阶段推进至更扎实的材料评估。',
                    'ko' => 'Studio 리뷰 키트는 초기 프로젝트 대화를 위해 마련되었습니다. 피니시 레퍼런스, 소형 오브제, 케어 노트를 결합하여 디자인 팀이 대량 주문 없이 영감에서 보다 구체적인 소재 검토로 나아갈 수 있도록 합니다.',
                ],

                'features' => [
                    'Finish chips and review references',
                    'Early-stage hospitality and design review',
                    'Bridges B2C discovery into future B2B supply',
                ],
                'features_translations' => [
                    'en' => [
                        'Finish chips and review references',
                        'Early-stage hospitality and design review',
                        'Bridges B2C discovery into future B2B supply',
                    ],
                    'zh' => [
                        '饰面样片与评审参考',
                        '早期阶段酒店与设计评估',
                        '从 B2C 发现到未来 B2B 供应的桥梁',
                    ],
                    'ko' => [
                        '피니시 칩과 검토 레퍼런스',
                        '초기 단계 호스피탈리티 및 디자인 검토',
                        'B2C 발견에서 미래 B2B 공급으로의 연결',
                    ],
                ],

                'availability_text' => 'Open for review-kit orders',
                'availability_text_translations' => [
                    'en' => 'Open for review-kit orders',
                    'zh' => '评估套件订单现已开放',
                    'ko' => '리뷰 키트 주문 접수 중',
                ],

                'lead_time' => 'Dispatches in 7-10 business days',
                'lead_time_translations' => [
                    'en' => 'Dispatches in 7-10 business days',
                    'zh' => '7–10 个工作日内发货',
                    'ko' => '영업일 기준 7–10일 내 발송',
                ],

                'care_instructions' => [
                    'Store review pieces flat and dry for finish comparison.',
                    'Contact OXP for project-specific care guidance.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Store review pieces flat and dry for finish comparison.',
                        'Contact OXP for project-specific care guidance.',
                    ],
                    'zh' => [
                        '将评审样件平放存储以便饰面对比。',
                        '如需项目专属护理指导，请联系 OXP。',
                    ],
                    'ko' => [
                        '피니시 비교를 위해 검토 피스는 평평하게 건조 보관.',
                        '프로젝트별 케어 지침은 OXP에 문의.',
                    ],
                ],

                'material_benefits' => [
                    'Low-friction way to evaluate the shell-led material story.',
                    'Connects premium B2C discovery with future B2B program planning.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Low-friction way to evaluate the shell-led material story.',
                        'Connects premium B2C discovery with future B2B program planning.',
                    ],
                    'zh' => [
                        '低门槛评估贝壳主导材料故事的方式。',
                        '将高端 B2C 探索与未来 B2B 项目规划相连接。',
                    ],
                    'ko' => [
                        '쉘 주도 소재 스토리를 평가하는 낮은 마찰 방식.',
                        '프리미엄 B2C 발견과 미래 B2B 프로그램 계획 연결.',
                    ],
                ],

                'seo_title' => 'Studio Review Kit | OXP designer review kit',
                'seo_title_translations' => [
                    'en' => 'Studio Review Kit | OXP designer review kit',
                    'zh' => 'Studio 评估套件 | OXP 设计师评估套件',
                    'ko' => 'Studio 리뷰 키트 | OXP 디자이너 리뷰 키트',
                ],

                'seo_description' => 'A compact OXP review kit for design teams, hospitality buyers, and project evaluation.',
                'seo_description_translations' => [
                    'en' => 'A compact OXP review kit for design teams, hospitality buyers, and project evaluation.',
                    'zh' => '一款专为设计团队、酒店采购方与项目评估设计的 OXP 紧凑型评审套件。',
                    'ko' => '디자인 팀, 호스피탈리티 바이어, 프로젝트 평가를 위한 컴팩트한 OXP 리뷰 키트.',
                ],

                'image_url' => '/images/process-collected.jpg',
                'gallery' => [
                    '/images/process-collected.jpg',
                    '/images/process-refined.jpg',
                    '/images/process-recrafted.jpg',
                ],

                'model' => 'lite_15',
                'finish' => 'matte',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'dimensions' => 'A5 kit box',
                'weight_grams' => 240,

                'attribute_specs' => [
                    [
                        'key' => 'contents',
                        'label' => 'Kit Contents',
                        'label_translations' => ['en' => 'Kit Contents', 'zh' => '套件内容', 'ko' => '키트 구성'],
                        'value' => 'Finish chips, small object, care notes',
                        'value_translations' => ['en' => 'Finish chips, small object, care notes', 'zh' => '饰面样片、小型实物、护理说明', 'ko' => '피니시 칩, 소형 오브제, 케어 노트'],
                        'group' => 'Product',
                    ],
                    [
                        'key' => 'audience',
                        'label' => 'Audience',
                        'label_translations' => ['en' => 'Audience', 'zh' => '目标用户', 'ko' => '대상'],
                        'value' => 'Design studios and hospitality buyers',
                        'value_translations' => ['en' => 'Design studios and hospitality buyers', 'zh' => '设计工作室与酒店采购方', 'ko' => '디자인 스튜디오와 호스피탈리티 바이어'],
                        'group' => 'Application',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['design_projects', 'hospitality_service', 'retail_gifting'],
                'price_from' => 36,
                'currency' => 'NZD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'sort_order' => 7,
                'compare_at_price_amount' => null,
                'price_amount' => 36,
                'stock_quantity' => null,
                'stock_status' => 'preorder',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'related' => [
                    'cove-display-tile',
                    'tidal-dinner-plate',
                    'shoreline-wellness-tray',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 8. Reef Candle Vessel
            // ─────────────────────────────────────────────────────────────────
            [
                'slug' => 'reef-candle-vessel',
                'sku' => 'REEF_CANDLE_VESSEL',
                'category' => 'wellness_interior',

                'name' => 'Reef Candle Vessel',
                'name_translations' => [
                    'en' => 'Reef Candle Vessel',
                    'zh' => 'Reef 香薰蜡烛容器',
                    'ko' => 'Reef 캔들 베셀',
                ],

                'subtitle' => 'Candle-ready vessel for premium interiors and quiet gifting.',
                'subtitle_translations' => [
                    'en' => 'Candle-ready vessel for premium interiors and quiet gifting.',
                    'zh' => '面向高端室内陈设与静谧礼赠场景的蜡烛容器。',
                    'ko' => '프리미엄 인테리어와 차분한 선물 제안을 위한 캔들 용기.',
                ],

                'short_description' => 'A shell composite vessel designed for candle programs, guest-room styling, and seasonal gifting capsules.',
                'short_description_translations' => [
                    'en' => 'A shell composite vessel designed for candle programs, guest-room styling, and seasonal gifting capsules.',
                    'zh' => '一款专为蜡烛协作项目、客房陈设与季节性礼赠胶囊设计的贝壳复合材质容器。',
                    'ko' => '캔들 프로그램, 객실 스타일링, 계절 선물 캡슐을 위해 설계된 쉘 복합 소재 용기.',
                ],

                'full_description' => 'Reef is currently between production runs and is useful as a reference case for the out-of-stock journey. The form is intended for boutique candle collaborations, guest-room amenities, and smaller branded gifting programs where the container itself adds material value.',
                'full_description_translations' => [
                    'en' => 'Reef is currently between production runs and is useful as a reference case for the out-of-stock journey. The form is intended for boutique candle collaborations, guest-room amenities, and smaller branded gifting programs where the container itself adds material value.',
                    'zh' => 'Reef 目前处于批次间隔期，适合作为缺货场景的参考案例。这款容器专为精品蜡烛协作、客房设施与小型品牌礼赠项目设计，在这些场景中，容器本身就承载着材料价值。',
                    'ko' => 'Reef는 현재 생산 배치 사이에 있으며 품절 여정의 참조 케이스로 유용합니다. 이 형태는 부티크 캔들 협업, 객실 어메니티, 그리고 용기 자체가 소재적 가치를 더하는 소형 브랜드 선물 프로그램을 위해 설계되었습니다.',
                ],

                'features' => [
                    'Candle-ready interior form',
                    'Premium gifting silhouette',
                    'Hospitality amenity potential',
                ],
                'features_translations' => [
                    'en' => [
                        'Candle-ready interior form',
                        'Premium gifting silhouette',
                        'Hospitality amenity potential',
                    ],
                    'zh' => [
                        '适合注蜡的内部形态',
                        '高端礼赠轮廓',
                        '酒店设施潜力',
                    ],
                    'ko' => [
                        '캔들에 적합한 내부 형태',
                        '프리미엄 선물 실루엣',
                        '호스피탈리티 어메니티 잠재력',
                    ],
                ],

                'availability_text' => 'Next production batch not yet scheduled',
                'availability_text_translations' => [
                    'en' => 'Next production batch not yet scheduled',
                    'zh' => '下一批次生产计划尚未确定',
                    'ko' => '다음 생산 배치 일정 미정',
                ],

                'lead_time' => 'Join the waitlist or request a hospitality update',
                'lead_time_translations' => [
                    'en' => 'Join the waitlist or request a hospitality update',
                    'zh' => '加入候补名单或咨询酒店采购更新',
                    'ko' => '대기자 명단 등록 또는 호스피탈리티 업데이트 요청',
                ],

                'care_instructions' => [
                    'Keep away from open flame until a certified fill program is approved.',
                    'Request a review pack or restock update for collaboration planning.',
                ],
                'care_instructions_translations' => [
                    'en' => [
                        'Keep away from open flame until a certified fill program is approved.',
                        'Request a review pack or restock update for collaboration planning.',
                    ],
                    'zh' => [
                        '在获得认证填充方案批准前，请远离明火。',
                        '请索取评审套件或补货更新以规划协作项目。',
                    ],
                    'ko' => [
                        '인증된 충전 프로그램 승인 전 화염 근처 사용 금지.',
                        '협업 계획을 위한 검토 팩 또는 재입고 업데이트 요청.',
                    ],
                ],

                'material_benefits' => [
                    'Strong premium storytelling for fragrance and wellness collaborations.',
                    'Extends the shell-origin narrative into intimate interior rituals.',
                ],
                'material_benefits_translations' => [
                    'en' => [
                        'Strong premium storytelling for fragrance and wellness collaborations.',
                        'Extends the shell-origin narrative into intimate interior rituals.',
                    ],
                    'zh' => [
                        '为香氛与健康协作提供有力的高端叙事。',
                        '将贝壳原产叙事延伸至亲密室内仪式之中。',
                    ],
                    'ko' => [
                        '향과 웰니스 협업을 위한 강력한 프리미엄 스토리텔링.',
                        '쉘 원산 내러티브를 친밀한 인테리어 리추얼로 확장.',
                    ],
                ],

                'seo_title' => 'Reef Candle Vessel | OXP premium candle collaboration vessel',
                'seo_title_translations' => [
                    'en' => 'Reef Candle Vessel | OXP premium candle collaboration vessel',
                    'zh' => 'Reef 香薰蜡烛容器 | OXP 高端蜡烛协作容器',
                    'ko' => 'Reef 캔들 베셀 | OXP 프리미엄 캔들 협업 용기',
                ],

                'seo_description' => 'A currently sold-out OXP vessel designed for premium candle and hospitality amenity concepts.',
                'seo_description_translations' => [
                    'en' => 'A currently sold-out OXP vessel designed for premium candle and hospitality amenity concepts.',
                    'zh' => '一款目前已售罄的 OXP 容器，专为高端蜡烛与酒店设施概念而设计。',
                    'ko' => '현재 품절된 OXP 용기로, 프리미엄 캔들 및 호스피탈리티 어메니티 콘셉트를 위해 설계되었습니다.',
                ],

                'image_url' => '/images/application-retail.jpg',
                'gallery' => [
                    '/images/application-retail.jpg',
                    '/images/application-packaging.jpg',
                    '/images/material-texture.jpg',
                ],

                'model' => 'heritage_16',
                'finish' => 'glossy',
                'color' => 'forged_ash',
                'technique' => 'driftwood_blend',
                'dimensions' => 'Dia 8.5 cm x H 9 cm',
                'weight_grams' => 420,

                'attribute_specs' => [
                    [
                        'key' => 'wax_fill',
                        'label' => 'Wax Fill Guideline',
                        'label_translations' => ['en' => 'Wax Fill Guideline', 'zh' => '灌蜡指引', 'ko' => '왁스 충전 가이드'],
                        'value' => 'Discuss with OXP team',
                        'value_translations' => ['en' => 'Discuss with OXP team', 'zh' => '请咨询 OXP 团队', 'ko' => 'OXP 팀과 협의'],
                        'group' => 'Program',
                    ],
                    [
                        'key' => 'batch_state',
                        'label' => 'Batch Status',
                        'label_translations' => ['en' => 'Batch Status', 'zh' => '批次状态', 'ko' => '배치 상태'],
                        'value' => 'Sold out',
                        'value_translations' => ['en' => 'Sold out', 'zh' => '已售罄', 'ko' => '품절'],
                        'group' => 'Availability',
                    ],
                ],

                'certifications' => [],
                'use_case_values' => ['interior_styling', 'retail_gifting', 'hospitality_service'],
                'price_from' => 72,
                'currency' => 'NZD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => false,
                'sort_order' => 8,
                'compare_at_price_amount' => 84,
                'price_amount' => 72,
                'stock_quantity' => 0,
                'stock_status' => 'sold_out',
                'in_stock' => false,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'related' => [
                    'shoreline-wellness-tray',
                    'drift-planter-no-2',
                    'studio-review-kit',
                ],
            ],
        ];

        $attributeDefinitions = ProductAttributeDefinition::query()
            ->with('values')
            ->whereIn('key', ['material_family', 'model', 'finish', 'color', 'technique', 'use_case', 'dimensions'])
            ->get()
            ->keyBy('key');

        // Update label_translations for pre-existing base definitions
        foreach ($attributeDefinitions as $key => $definition) {
            if (isset(self::DEFINITION_LABEL_TRANSLATIONS[$key])) {
                $definition->update(['label_translations' => self::DEFINITION_LABEL_TRANSLATIONS[$key]]);
            }
        }

        $records = [];

        foreach ($products as $productData) {
            /** @var ProductCategory $category */
            $category = $categories[$productData['category']];

            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'name_translations' => $productData['name_translations'],
                    'subtitle' => $productData['subtitle'],
                    'subtitle_translations' => $productData['subtitle_translations'],
                    'short_description' => $productData['short_description'],
                    'short_description_translations' => $productData['short_description_translations'],
                    'full_description' => $productData['full_description'],
                    'full_description_translations' => $productData['full_description_translations'],
                    'features' => $productData['features'],
                    'features_translations' => $productData['features_translations'],
                    'availability_text' => $productData['availability_text'],
                    'availability_text_translations' => $productData['availability_text_translations'],
                    'lead_time' => $productData['lead_time'],
                    'lead_time_translations' => $productData['lead_time_translations'],
                    'weight_grams' => $productData['weight_grams'],
                    'care_instructions' => $productData['care_instructions'],
                    'care_instructions_translations' => $productData['care_instructions_translations'],
                    'material_benefits' => $productData['material_benefits'],
                    'material_benefits_translations' => $productData['material_benefits_translations'],
                    'certifications' => [],
                    'technical_downloads' => [],
                    'seo_title' => $productData['seo_title'],
                    'seo_title_translations' => $productData['seo_title_translations'],
                    'seo_description' => $productData['seo_description'],
                    'seo_description_translations' => $productData['seo_description_translations'],
                    'status' => ProductStatus::Published->value,
                    'featured' => $productData['featured'],
                    'is_bestseller' => $productData['is_bestseller'],
                    'is_new' => $productData['is_new'],
                    'sort_order' => $productData['sort_order'],
                    'image_url' => $productData['image_url'],
                    'price_from' => $productData['price_from'],
                    'currency' => $productData['currency'],
                    'inquiry_only' => $productData['inquiry_only'],
                    'sample_request_enabled' => $productData['sample_request_enabled'],
                    'is_active' => true,
                    'published_at' => now(),
                    ...$this->initialMetadata(),
                ],
            );

            ProductVariant::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sku' => $productData['sku'],
                ],
                [
                    'title' => 'Default',
                    'price_amount' => $productData['price_amount'],
                    'compare_at_price_amount' => $productData['compare_at_price_amount'],
                    'currency' => $productData['currency'],
                    'stock_quantity' => $productData['stock_quantity'],
                    'stock_status' => $productData['stock_status'],
                    'inventory_policy' => 'deny',
                    'weight_grams' => $productData['weight_grams'],
                    'image_url' => $productData['image_url'],
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 0,
                    ...$this->initialMetadata(),
                ],
            );

            $this->syncDynamicAttributes($product, $productData, $attributeDefinitions);

            foreach ($productData['gallery'] as $index => $mediaUrl) {
                ProductImage::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sort_order' => $index + 1,
                    ],
                    [
                        'alt_text' => $product->name,
                        'alt_text_translations' => $product->name_translations,
                        'caption' => $index === 0 ? $product->short_description : $product->subtitle,
                        'caption_translations' => $index === 0
                            ? ($product->short_description_translations ?? [])
                            : ($product->subtitle_translations ?? []),
                        'media_url' => $mediaUrl,
                        ...$this->initialMetadata(),
                    ],
                );
            }

            $records[$productData['slug']] = $product;
        }

        foreach ($products as $productData) {
            $relatedIds = collect($productData['related'] ?? [])
                ->map(fn (string $slug): ?int => $records[$slug]->id ?? null)
                ->filter(fn (?int $id): bool => $id !== null && $id !== $records[$productData['slug']]->id)
                ->values()
                ->all();

            $records[$productData['slug']]->relatedProducts()->sync($relatedIds);
        }
    }

    private function ensureBaseDefinitions(): void
    {
        $baseDefinitions = [
            ['key' => 'material_family', 'label' => 'Material Family', 'sort_order' => 1,  'is_filterable' => true,  'is_searchable' => false, 'allows_multiple' => false],
            ['key' => 'model',           'label' => 'Model',           'sort_order' => 2,  'is_filterable' => true,  'is_searchable' => false, 'allows_multiple' => false],
            ['key' => 'finish',          'label' => 'Finish',          'sort_order' => 3,  'is_filterable' => true,  'is_searchable' => false, 'allows_multiple' => false],
            ['key' => 'color',           'label' => 'Color',           'sort_order' => 4,  'is_filterable' => true,  'is_searchable' => false, 'allows_multiple' => false],
            ['key' => 'technique',       'label' => 'Technique',       'sort_order' => 5,  'is_filterable' => true,  'is_searchable' => false, 'allows_multiple' => false],
            ['key' => 'use_case',        'label' => 'Use Case',        'sort_order' => 6,  'is_filterable' => true,  'is_searchable' => true,  'allows_multiple' => true],
            ['key' => 'dimensions',      'label' => 'Dimensions',      'sort_order' => 10, 'is_filterable' => false, 'is_searchable' => false, 'allows_multiple' => false],
        ];

        foreach ($baseDefinitions as $def) {
            $translations = self::DEFINITION_LABEL_TRANSLATIONS[$def['key']];

            // Only create if absent — never overwrite existing settings
            $definition = ProductAttributeDefinition::query()->firstOrCreate(
                ['key' => $def['key']],
                [
                    'label' => $def['label'],
                    'label_translations' => $translations,
                    'type' => 'select',
                    'group' => 'Material',
                    'is_variant_option' => false,
                    'is_filterable' => $def['is_filterable'],
                    'is_searchable' => $def['is_searchable'],
                    'is_specification' => false,
                    'is_required' => false,
                    'allows_multiple' => $def['allows_multiple'],
                    'sort_order' => $def['sort_order'],
                    'is_active' => true,
                ],
            );

            // Always update label_translations so zh/ko are kept in sync
            if (($definition->label_translations['zh'] ?? null) === null
                || ($definition->label_translations['ko'] ?? null) === null) {
                $definition->update(['label_translations' => $translations]);
            }
        }
    }

    private function syncDynamicAttributes(Product $product, array $productData, mixed $attributeDefinitions): void
    {
        $product->attributeAssignments()->delete();

        $assignments = [
            'material_family' => ['oxp'],
            'model' => [$productData['model']],
            'finish' => [$productData['finish']],
            'color' => [$productData['color']],
            'technique' => [$productData['technique']],
            'use_case' => $productData['use_case_values'] ?? [],
        ];

        foreach ($assignments as $definitionKey => $values) {
            $definition = $attributeDefinitions->get($definitionKey);

            if ($definition === null) {
                continue;
            }

            foreach ($values as $value) {
                $defaultLabel = Str::headline(str_replace('_', ' ', (string) $value));
                $labelTranslations = self::VALUE_LABEL_TRANSLATIONS[$value] ?? ['en' => $defaultLabel];

                $attributeValue = ProductAttributeValue::query()->updateOrCreate(
                    [
                        'attribute_definition_id' => $definition->id,
                        'value' => $value,
                    ],
                    [
                        'label' => $labelTranslations['en'],
                        'label_translations' => $labelTranslations,
                        'is_active' => true,
                    ],
                );

                ProductAttributeAssignment::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attribute_definition_id' => $definition->id,
                        'product_attribute_value_id' => $attributeValue->id,
                    ],
                    [
                        'value_text' => null,
                        'value_number' => null,
                        'value_boolean' => null,
                        'value_json' => null,
                        ...$this->initialMetadata(),
                    ],
                );
            }
        }

        if (filled($productData['dimensions'] ?? null) && $attributeDefinitions->has('dimensions')) {
            ProductAttributeAssignment::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_definition_id' => $attributeDefinitions->get('dimensions')->id,
                    'product_attribute_value_id' => null,
                ],
                [
                    'value_text' => $productData['dimensions'],
                    'value_number' => null,
                    'value_boolean' => null,
                    'value_json' => null,
                    ...$this->initialMetadata(),
                ],
            );
        }

        foreach ($productData['attribute_specs'] ?? [] as $specification) {
            if (! is_array($specification)) {
                continue;
            }

            $label = trim((string) ($specification['label'] ?? ''));
            $value = trim((string) ($specification['value'] ?? ''));

            if ($label === '' || $value === '') {
                continue;
            }

            $key = Str::slug((string) ($specification['key'] ?? $label), '_');
            $labelTranslations = $specification['label_translations'] ?? ['en' => $label];
            $valueTranslations = $specification['value_translations'] ?? ['en' => $value];

            $definition = ProductAttributeDefinition::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'label_translations' => $labelTranslations,
                    'type' => 'text',
                    'unit' => $specification['unit'] ?? null,
                    'group' => $specification['group'] ?? 'Specifications',
                    'is_variant_option' => false,
                    'is_filterable' => false,
                    'is_searchable' => false,
                    'is_specification' => true,
                    'is_required' => false,
                    'allows_multiple' => false,
                    'sort_order' => 100,
                    'is_active' => true,
                ],
            );

            ProductAttributeAssignment::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_definition_id' => $definition->id,
                    'product_attribute_value_id' => null,
                ],
                [
                    'value_text' => $value,
                    'value_number' => null,
                    'value_boolean' => null,
                    'value_json' => ['translations' => $valueTranslations],
                    ...$this->initialMetadata(),
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function initialMetadata(): array
    {
        return [
            'is_demo_content' => false,
            'seed_source' => self::SEED_SOURCE,
            'seeded_at' => now(),
        ];
    }
}
