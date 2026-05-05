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
                'headline' => 'A premium, science-backed material platform built from recovered shell.',
                'summary' => 'Designed for premium interior objects, hospitality programs, and future collaborative product development.',
                'story_overview' => 'Recovered oyster shells are cleaned, refined, and transformed into a premium composite that balances tactile quality with circular material storytelling.',
                'science_overview' => 'The material story is supported through measurable durability, reuse potential, and a lower-waste sourcing narrative that can be adapted for premium B2B and design-led applications.',
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
                'value' => 'Lightweight',
                'detail' => 'Suitable for portable premium objects and interior accessory systems.',
                'sort_order' => 1,
            ],
            [
                'key' => 'strength',
                'label' => 'Strength',
                'value' => 'High compressive stability',
                'detail' => 'Built for premium display, tabletop, and light-use structural applications.',
                'sort_order' => 2,
            ],
            [
                'key' => 'flexibility',
                'label' => 'Flexibility',
                'value' => 'Process-dependent',
                'detail' => 'Can be tuned across rigid and semi-rigid outputs depending on the formulation.',
                'sort_order' => 3,
            ],
            [
                'key' => 'durability',
                'label' => 'Durability',
                'value' => 'Long-wear surface performance',
                'detail' => 'Designed for repeated handling in premium commercial and hospitality environments.',
                'sort_order' => 4,
            ],
            [
                'key' => 'carbon_footprint',
                'label' => 'Carbon Footprint Reduction',
                'value' => 'Waste-stream reuse',
                'detail' => 'Supports a lower-waste sourcing story by redirecting shell by-product into durable new applications.',
                'sort_order' => 5,
            ],
            [
                'key' => 'circularity',
                'label' => 'Circularity',
                'value' => 'Recycled shell input',
                'detail' => 'Built around reuse, traceability, and circular material communication for premium brands.',
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
                    'value' => $spec['value'],
                    'detail' => $spec['detail'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $spec['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

        $storySections = [
            [
                'title' => 'From Shell Waste to Premium Feedstock',
                'subtitle' => 'Recovery and refinement',
                'content' => 'Shell waste is collected, cleaned, graded, and prepared as a premium feedstock that can support consistent downstream production storytelling.',
                'highlight' => 'Recovered waste stream',
                'sort_order' => 1,
            ],
            [
                'title' => 'Material Science for Commercial Credibility',
                'subtitle' => 'Performance and trust',
                'content' => 'The platform is positioned to communicate measurable performance, circularity, and premium fit for brands, institutions, and design collaborators.',
                'highlight' => 'Science-backed positioning',
                'sort_order' => 2,
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
                    'content' => $section['content'],
                    'highlight' => $section['highlight'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $section['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

        $applications = [
            [
                'title' => 'Hospitality and Tabletop',
                'subtitle' => 'Premium experiential objects',
                'description' => 'Suitable for trays, serving accessories, and branded hospitality surfaces where material story matters.',
                'audience' => 'Hotels, restaurants, experience brands',
                'cta_label' => 'Discuss hospitality use',
                'cta_url' => 'https://example.com/collaboration/hospitality',
                'sort_order' => 1,
            ],
            [
                'title' => 'Retail and Brand Installations',
                'subtitle' => 'Display and merchandising',
                'description' => 'Supports premium merchandising systems, plinths, props, and material storytelling moments in physical retail.',
                'audience' => 'Retail teams, creative agencies, brand studios',
                'cta_label' => 'Discuss retail application',
                'cta_url' => 'https://example.com/collaboration/retail',
                'sort_order' => 2,
            ],
            [
                'title' => 'University and Product Development Partnerships',
                'subtitle' => 'Research-led collaboration',
                'description' => 'Provides a base layer for co-development, prototyping, and future crowdfunding-ready concepts.',
                'audience' => 'Universities, SMEs, product teams',
                'cta_label' => 'Start a collaboration',
                'cta_url' => 'https://example.com/collaboration/research',
                'sort_order' => 3,
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
                    'description' => $application['description'],
                    'audience' => $application['audience'],
                    'cta_label' => $application['cta_label'],
                    'cta_url' => $application['cta_url'],
                    'status' => PublishStatus::Published->value,
                    'sort_order' => $application['sort_order'],
                    'published_at' => $publishedAt,
                ]
            );
        }

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
