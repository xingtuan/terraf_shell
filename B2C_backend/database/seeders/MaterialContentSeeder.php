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
                'excerpt' => 'The oyster-shell material showcase is now structured for premium storytelling, collaboration, and future support flows.',
                'content' => 'This launch establishes the backend foundation for premium material storytelling, editorial updates, and home page content management through the API.',
                'category' => 'updates',
                'sort_order' => 1,
            ],
            [
                'slug' => 'science-notes-shell-composite',
                'title' => 'Science Notes on the Oyster Shell Composite',
                'excerpt' => 'A high-level summary of performance, circularity, and positioning signals available for the frontend showcase.',
                'content' => 'This editorial entry can be used by the frontend to render science-backed context around durability, circularity, and premium application fit.',
                'category' => 'science',
                'sort_order' => 2,
            ],
        ];

        foreach ($articles as $article) {
            Article::query()->updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'content' => $article['content'],
                    'category' => $article['category'],
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
                'content' => 'Use the homepage API to drive hero messaging, CTA text, and premium material storytelling without code changes.',
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
                'subtitle' => 'Material proof points',
                'content' => 'Frontend sections can render measured qualities, recycled-origin messaging, and application fit from the CMS payload.',
                'cta_label' => 'Review specs',
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
                'subtitle' => 'Articles and updates',
                'content' => 'Published articles are exposed through the API and can be surfaced on the homepage dynamically.',
                'cta_label' => 'Read updates',
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
                    'subtitle' => $section['subtitle'],
                    'content' => $section['content'],
                    'cta_label' => $section['cta_label'],
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
