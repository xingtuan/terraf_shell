<?php

namespace Tests\Feature\Api;

use App\Enums\PublishStatus;
use App\Filament\Resources\HomeSections\Pages\EditHomeSection;
use App\Models\Article;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_material_endpoint_returns_cms_material_payload_and_homepage_still_returns_published_content(): void
    {
        $material = Material::factory()->published()->create([
            'slug' => 'oyster-shell-material',
            'is_featured' => true,
            'sort_order' => 1,
            'headline' => "Ocean's Legacy, Crafted with Artisan Tech.",
            'headline_translations' => [
                'zh' => '海洋遗产，以匠人工艺重塑。',
                'ko' => '바다의 유산을 장인의 기술로 다시 빚다.',
            ],
            'summary' => 'Recycled oyster shells collected from coastal waste streams',
            'summary_translations' => [
                'zh' => '从沿海废弃物流中回收的牡蛎壳',
                'ko' => '해안 폐기물 흐름에서 회수한 굴 껍데기',
            ],
            'certifications' => [
                [
                    'name' => 'Water Absorption Test',
                    'result' => null,
                    'unit' => null,
                    'status' => 'pending',
                    'description' => 'Client confirmation pending before publication.',
                    'issuer' => 'Client confirmation pending',
                    'tested_at' => null,
                    'document_url' => null,
                ],
            ],
            'technical_downloads' => [],
        ]);
        Material::factory()->create([
            'slug' => 'draft-material',
        ]);

        MaterialSpec::factory()->published()->create([
            'material_id' => $material->id,
            'label' => 'Durability',
            'label_translations' => [
                'zh' => '轻量化',
                'ko' => '충격 저항성',
            ],
            'sort_order' => 1,
        ]);
        MaterialSpec::factory()->create([
            'material_id' => $material->id,
            'label' => 'Draft spec',
        ]);

        MaterialStorySection::factory()->published()->create([
            'material_id' => $material->id,
            'title' => 'Recovery process',
            'title_translations' => [
                'zh' => '收集',
                'ko' => '열 정화',
            ],
            'sort_order' => 1,
        ]);
        MaterialStorySection::factory()->create([
            'material_id' => $material->id,
            'title' => 'Draft story',
        ]);

        MaterialApplication::factory()->published()->create([
            'material_id' => $material->id,
            'title' => 'Hospitality',
            'sort_order' => 1,
        ]);
        MaterialApplication::factory()->create([
            'material_id' => $material->id,
            'title' => 'Draft application',
        ]);

        HomeSection::query()->updateOrCreate([
            'page_key' => 'home',
            'key' => 'hero',
        ], [
            'title' => 'Homepage hero',
            'content' => 'Homepage hero content',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        HomeSection::factory()->create([
            'key' => 'draft_hero',
        ]);

        Article::factory()->published()->create([
            'slug' => 'lab-update',
            'title' => 'Lab update',
        ]);
        Article::factory()->create([
            'slug' => 'draft-update',
            'title' => 'Draft update',
        ]);

        $this->getJson('/api/materials')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $material->title)
            ->assertJsonPath('data.tagline', $material->headline)
            ->assertJsonPath('data.origin', $material->summary)
            ->assertJsonCount(1, 'data.process_steps')
            ->assertJsonCount(1, 'data.properties')
            ->assertJsonCount(1, 'data.certifications')
            ->assertJsonPath('data.certifications.0.name', 'Water Absorption Test')
            ->assertJsonPath('data.certifications.0.status', 'pending')
            ->assertJsonCount(0, 'data.technical_downloads')
            ->assertJsonPath('data.models.0.id', 'pellet_input')
            ->assertJsonPath('data.colors.1.id', 'thermal_ash');

        $this->getJson('/api/materials/oyster-shell-material')
            ->assertOk()
            ->assertJsonPath('data.name', $material->title)
            ->assertJsonPath('data.certifications.0.status', 'pending');

        $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.tagline', '海洋遗产，以匠人工艺重塑。')
            ->assertJsonPath('data.origin', '从沿海废弃物流中回收的牡蛎壳')
            ->assertJsonPath('data.process_steps.0.title', '收集')
            ->assertJsonPath('data.properties.0.label', '轻量化')
            ->assertJsonPath('data.models.0.name', '颗粒原料')
            ->assertJsonPath('data.colors.0.name', '牡蛎矿物白');

        $this->getJson('/api/materials?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.tagline', '바다의 유산을 장인의 기술로 다시 빚다.')
            ->assertJsonPath('data.origin', '해안 폐기물 흐름에서 회수한 굴 껍데기')
            ->assertJsonPath('data.process_steps.0.title', '열 정화')
            ->assertJsonPath('data.properties.0.label', '충격 저항성')
            ->assertJsonPath('data.models.0.name', '펠릿 원료')
            ->assertJsonPath('data.colors.1.name', '써멀 애시');

        $this->getJson('/api/home-sections')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'hero')
            ->assertJsonFragment(['key' => 'pilot_projects'])
            ->assertJsonFragment(['page_key' => 'home']);

        $this->getJson('/api/articles')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'lab-update');

        $this->getJson('/api/homepage')
            ->assertOk()
            ->assertJsonCount(1, 'data.materials')
            ->assertJsonCount(1, 'data.articles')
            ->assertJsonPath('data.materials.0.slug', 'oyster-shell-material');
    }

    public function test_public_material_endpoint_returns_chinese_material_payload_without_english_fallback_copy(): void
    {
        $response = $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.tagline', '一种基于回收牡蛎壳、具备科学验证方向的高端材料平台。')
            ->assertJsonPath('data.origin', '面向高端室内物件、酒店餐饮项目、餐具概念以及未来联合产品开发。')
            ->assertJsonCount(0, 'data.certifications')
            ->assertJsonCount(0, 'data.technical_downloads');

        $response->assertDontSee([
            "Ocean's Legacy, Crafted with Artisan Tech.",
            'Water Absorption Test',
        ], false);
    }

    public function test_admin_can_crud_material_cms_content_with_media(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $materialResponse = $this->post('/api/admin/materials', [
            'title' => 'Premium Oyster Shell',
            'headline' => 'Science-backed shell composite',
            'summary' => 'Core showcase material.',
            'story_overview' => 'Recovered shell, refined into pellets, shaped into premium objects.',
            'science_overview' => 'Validated for premium applications.',
            'status' => 'published',
            'is_featured' => true,
            'sort_order' => 1,
            'media' => UploadedFile::fake()->create('material.jpg', 120, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $materialResponse
            ->assertCreated()
            ->assertJsonPath('data.title', 'Premium Oyster Shell')
            ->assertJsonPath('data.status', 'published');

        $materialId = $materialResponse->json('data.id');

        $this->postJson('/api/admin/material-specs', [
            'material_id' => $materialId,
            'key' => 'durability',
            'label' => 'Durability',
            'value' => 'High',
            'detail' => 'Dense compression improves edge stability.',
            'status' => 'published',
            'sort_order' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('data.material_id', $materialId);

        $this->postJson('/api/admin/material-story-sections', [
            'material_id' => $materialId,
            'title' => 'Shell recovery',
            'content' => 'Collected oyster shell is cleaned and prepared for material recovery.',
            'status' => 'published',
            'sort_order' => 1,
        ])->assertCreated();

        $this->postJson('/api/admin/material-applications', [
            'material_id' => $materialId,
            'title' => 'Retail objects',
            'description' => 'Use in premium tabletop, gifting, and interior programs.',
            'cta_label' => 'Discuss application',
            'cta_url' => 'https://example.com/applications',
            'status' => 'published',
            'sort_order' => 1,
        ])->assertCreated();

        $this->post('/api/admin/articles', [
            'title' => 'Material launch update',
            'content' => 'Published article content.',
            'category' => 'updates',
            'status' => 'published',
            'media' => UploadedFile::fake()->create('article.jpg', 120, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'published');

        $homeSectionResponse = $this->postJson('/api/admin/home-sections', [
            'key' => 'custom_hero',
            'title' => 'Premium oyster shell materials',
            'content' => 'Homepage hero content',
            'payload' => ['variant' => 'hero'],
            'status' => 'published',
            'sort_order' => 1,
        ])->assertCreated();

        $homeSectionId = $homeSectionResponse->json('data.id');

        $this->getJson('/api/admin/materials')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->patchJson("/api/admin/materials/{$materialId}", [
            'headline' => 'Updated science-backed shell composite',
            'remove_media' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.headline', 'Updated science-backed shell composite')
            ->assertJsonPath('data.media_url', null);

        $this->deleteJson("/api/admin/home-sections/{$homeSectionId}")
            ->assertOk();

    }

    public function test_non_admin_users_cannot_manage_material_cms_modules(): void
    {
        $moderator = User::factory()->moderator()->create();
        Sanctum::actingAs($moderator);

        $this->postJson('/api/admin/materials', [
            'title' => 'Blocked',
            'status' => 'published',
        ])->assertForbidden();
    }

    public function test_public_article_and_home_section_endpoints_resolve_requested_locale_when_translations_exist(): void
    {
        Article::factory()->published()->create([
            'slug' => 'localized-article',
            'title' => 'English article',
            'title_translations' => [
                'en' => 'English article',
                'ko' => 'KO article',
                'zh' => 'ZH article',
            ],
        ]);

        HomeSection::factory()->published()->create([
            'key' => 'localized_hero',
            'title' => 'English section',
            'title_translations' => [
                'en' => 'English section',
                'ko' => 'KO section',
                'zh' => 'ZH section',
            ],
        ]);

        $this->getJson('/api/articles?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'ZH article');

        $this->getJson('/api/home-sections?locale=ko')
            ->assertOk()
            ->assertJsonFragment(['title' => 'KO section']);
    }

    public function test_public_home_sections_can_be_filtered_by_page_key(): void
    {
        HomeSection::factory()->published()->create([
            'page_key' => 'home',
            'key' => 'shared_test_section',
            'title' => 'Home scoped section',
        ]);
        HomeSection::factory()->published()->create([
            'page_key' => 'material',
            'key' => 'shared_test_section',
            'title' => 'Material scoped section',
        ]);

        $this->getJson('/api/home-sections?page=material')
            ->assertOk()
            ->assertJsonFragment([
                'page_key' => 'material',
                'key' => 'shared_test_section',
                'title' => 'Material scoped section',
            ])
            ->assertJsonMissing([
                'title' => 'Home scoped section',
            ]);

        $this->getJson('/api/page-sections?page=home')
            ->assertOk()
            ->assertJsonFragment([
                'page_key' => 'home',
                'key' => 'shared_test_section',
                'title' => 'Home scoped section',
            ])
            ->assertJsonMissing([
                'title' => 'Material scoped section',
            ]);
    }

    public function test_public_page_sections_cover_all_page_keys_and_normalize_payload_maps(): void
    {
        HomeSection::query()->delete();

        $pageKeys = ['home', 'material', 'contact', 'b2b', 'store', 'community', 'articles'];

        foreach ($pageKeys as $index => $pageKey) {
            HomeSection::factory()->published()->create([
                'page_key' => $pageKey,
                'key' => "{$pageKey}_visible",
                'title' => "EN {$pageKey}",
                'title_translations' => [
                    'en' => "EN {$pageKey}",
                    'zh' => "ZH {$pageKey}",
                    'ko' => "KO {$pageKey}",
                ],
                'payload' => [
                    'items' => [
                        '550e8400-e29b-41d4-a716-446655440000' => [
                            'title' => 'Second',
                            'sort_order' => 2,
                        ],
                        '550e8400-e29b-41d4-a716-446655440001' => [
                            'title' => 'First',
                            'sort_order' => 1,
                        ],
                    ],
                    'cards' => [
                        '10' => [
                            'title' => 'Numeric key card',
                        ],
                    ],
                ],
                'sort_order' => $index,
            ]);

            HomeSection::factory()->create([
                'page_key' => $pageKey,
                'key' => "{$pageKey}_draft",
                'status' => PublishStatus::Draft->value,
            ]);
        }

        foreach ($pageKeys as $pageKey) {
            $response = $this->getJson("/api/page-sections?page={$pageKey}&locale=ko")
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment([
                    'page_key' => $pageKey,
                    'key' => "{$pageKey}_visible",
                    'title' => "KO {$pageKey}",
                ])
                ->assertJsonMissing([
                    'key' => "{$pageKey}_draft",
                ]);

            $section = $response->json('data.0');

            $this->assertSame([0, 1], array_keys($section['payload']['items']));
            $this->assertSame('Second', $section['payload']['items'][0]['title']);
            $this->assertSame([0], array_keys($section['payload']['cards']));
            $this->assertSame('Numeric key card', $section['payload']['cards'][0]['title']);
        }
    }

    public function test_home_section_visibility_toggle_persists_status_and_controls_public_api_visibility(): void
    {
        HomeSection::query()->delete();

        $admin = User::factory()->admin()->create();
        $section = HomeSection::factory()->published()->create([
            'page_key' => 'home',
            'key' => 'visibility_toggle_section',
            'title' => 'Visibility toggle section',
        ]);
        HomeSection::factory()->create([
            'page_key' => 'home',
            'key' => 'draft_section',
            'status' => PublishStatus::Draft->value,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditHomeSection::class, ['record' => $section->getKey()])
            ->assertFormSet([
                'show_on_frontend' => true,
            ])
            ->fillForm([
                'show_on_frontend' => 'false',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $section->refresh();

        $this->assertSame(PublishStatus::Draft->value, $section->status);
        $this->assertNull($section->published_at);

        Livewire::test(EditHomeSection::class, ['record' => $section->getKey()])
            ->assertFormSet([
                'show_on_frontend' => false,
            ]);

        $this->getJson('/api/page-sections?page=home')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing([
                'key' => 'visibility_toggle_section',
            ]);

        Livewire::test(EditHomeSection::class, ['record' => $section->getKey()])
            ->fillForm([
                'show_on_frontend' => '1',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $section->refresh();

        $this->assertSame(PublishStatus::Published->value, $section->status);
        $this->assertNotNull($section->published_at);

        $this->getJson('/api/page-sections?page=home')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'key' => 'visibility_toggle_section',
                'status' => PublishStatus::Published->value,
            ])
            ->assertJsonMissing([
                'key' => 'draft_section',
            ]);
    }

    public function test_home_section_legacy_status_values_are_normalized(): void
    {
        foreach ([
            'visible',
            'ACTIVE',
            'frontend',
            true,
            '1',
            '前端显示',
            PublishStatus::Published,
        ] as $status) {
            $section = HomeSection::factory()->create([
                'status' => $status,
                'published_at' => null,
            ])->fresh();

            $this->assertSame(PublishStatus::Published->value, $section->status);
            $this->assertNotNull($section->published_at);
        }

        $archived = HomeSection::factory()->create([
            'status' => 'ARCHIVED',
            'published_at' => now(),
        ])->fresh();

        $draft = HomeSection::factory()->create([
            'status' => 'hidden',
            'published_at' => now(),
        ])->fresh();

        $this->assertSame(PublishStatus::Archived->value, $archived->status);
        $this->assertNull($archived->published_at);
        $this->assertSame(PublishStatus::Draft->value, $draft->status);
        $this->assertNull($draft->published_at);
    }

    public function test_contact_and_b2b_page_sections_expose_locale_specific_cms_content(): void
    {
        HomeSection::query()->updateOrCreate([
            'page_key' => 'contact',
            'key' => 'intro',
        ], [
            'title' => 'Contact title',
            'title_translations' => [
                'en' => 'Contact title',
                'ko' => 'KO contact title',
                'zh' => 'ZH contact title',
            ],
            'content' => 'Contact description',
            'content_translations' => [
                'en' => 'Contact description',
                'ko' => 'KO contact description',
                'zh' => 'ZH contact description',
            ],
            'status' => 'published',
            'published_at' => now(),
        ]);

        HomeSection::query()->updateOrCreate([
            'page_key' => 'b2b',
            'key' => 'process',
        ], [
            'title' => 'B2B process',
            'title_translations' => [
                'en' => 'B2B process',
                'ko' => 'KO B2B process',
                'zh' => 'ZH B2B process',
            ],
            'payload' => [
                'items' => [
                    [
                        'title_translations' => [
                            'en' => 'Step one',
                            'ko' => 'KO step one',
                            'zh' => 'ZH step one',
                        ],
                    ],
                ],
            ],
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->getJson('/api/page-sections?page=contact&locale=ko')
            ->assertOk()
            ->assertJsonFragment([
                'page_key' => 'contact',
                'key' => 'intro',
                'title' => 'KO contact title',
                'content' => 'KO contact description',
            ]);

        $this->getJson('/api/page-sections?page=b2b&locale=zh')
            ->assertOk()
            ->assertJsonFragment([
                'page_key' => 'b2b',
                'key' => 'process',
                'title' => 'ZH B2B process',
            ])
            ->assertJsonFragment([
                'zh' => 'ZH step one',
            ]);
    }

    public function test_public_apis_return_admin_edited_database_cms_content_with_translations(): void
    {
        $material = Material::factory()->published()->create([
            'slug' => 'admin-edited-material',
            'title' => 'Admin edited material',
            'title_translations' => [
                'en' => 'Admin edited material',
                'ko' => 'KO admin material',
                'zh' => 'ZH admin material',
            ],
            'headline' => 'Admin edited headline',
            'headline_translations' => [
                'en' => 'Admin edited headline',
                'ko' => 'KO admin headline',
                'zh' => 'ZH admin headline',
            ],
            'summary' => 'Admin edited summary',
            'summary_translations' => [
                'en' => 'Admin edited summary',
                'ko' => 'KO admin summary',
                'zh' => 'ZH admin summary',
            ],
            'is_featured' => true,
            'sort_order' => 1,
        ]);

        MaterialSpec::factory()->published()->create([
            'material_id' => $material->id,
            'key' => 'admin_strength',
            'label' => 'Admin strength',
            'label_translations' => [
                'en' => 'Admin strength',
                'ko' => 'KO admin strength',
                'zh' => 'ZH admin strength',
            ],
            'value' => 'Admin value',
            'value_translations' => [
                'en' => 'Admin value',
                'ko' => 'KO admin value',
                'zh' => 'ZH admin value',
            ],
            'unit' => null,
            'sort_order' => 1,
        ]);

        MaterialStorySection::factory()->published()->create([
            'material_id' => $material->id,
            'title' => 'Admin story',
            'title_translations' => [
                'en' => 'Admin story',
                'ko' => 'KO admin story',
                'zh' => 'ZH admin story',
            ],
            'content' => 'Admin story content',
            'content_translations' => [
                'en' => 'Admin story content',
                'ko' => 'KO admin story content',
                'zh' => 'ZH admin story content',
            ],
            'sort_order' => 1,
        ]);

        MaterialApplication::factory()->published()->create([
            'material_id' => $material->id,
            'title' => 'Admin application',
            'title_translations' => [
                'en' => 'Admin application',
                'ko' => 'KO admin application',
                'zh' => 'ZH admin application',
            ],
            'description' => 'Admin application content',
            'description_translations' => [
                'en' => 'Admin application content',
                'ko' => 'KO admin application content',
                'zh' => 'ZH admin application content',
            ],
            'sort_order' => 1,
        ]);

        HomeSection::query()->updateOrCreate([
            'page_key' => 'home',
            'key' => 'hero',
        ], [
            'title' => 'Admin homepage hero',
            'title_translations' => [
                'en' => 'Admin homepage hero',
                'ko' => 'KO admin homepage hero',
                'zh' => 'ZH admin homepage hero',
            ],
            'cta_label' => 'Admin CTA',
            'cta_label_translations' => [
                'en' => 'Admin CTA',
                'ko' => 'KO admin CTA',
                'zh' => 'ZH admin CTA',
            ],
            'status' => 'published',
            'sort_order' => 1,
        ]);

        Article::factory()->published()->create([
            'slug' => 'admin-edited-article',
            'title' => 'Admin edited article',
            'title_translations' => [
                'en' => 'Admin edited article',
                'ko' => 'KO admin article',
                'zh' => 'ZH admin article',
            ],
            'category' => 'updates',
            'category_translations' => [
                'en' => 'updates',
                'ko' => 'KO updates',
                'zh' => 'ZH updates',
            ],
            'content' => 'Admin article content',
            'content_translations' => [
                'en' => 'Admin article content',
                'ko' => 'KO admin article content',
                'zh' => 'ZH admin article content',
            ],
            'sort_order' => 1,
        ]);

        $this->getJson('/api/homepage?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.home_sections.0.title', 'KO admin homepage hero')
            ->assertJsonPath('data.home_sections.0.cta_label', 'KO admin CTA')
            ->assertJsonPath('data.materials.0.title', 'KO admin material')
            ->assertJsonPath('data.articles.0.title', 'KO admin article');

        $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.name', 'ZH admin material')
            ->assertJsonPath('data.tagline', 'ZH admin headline')
            ->assertJsonPath('data.origin', 'ZH admin summary')
            ->assertJsonPath('data.properties.0.label', 'ZH admin strength')
            ->assertJsonPath('data.process_steps.0.title', 'ZH admin story')
            ->assertJsonPath('data.applications.0.title', 'ZH admin application');

        $this->getJson('/api/articles?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'KO admin article')
            ->assertJsonPath('data.0.category', 'KO updates');
    }
}
