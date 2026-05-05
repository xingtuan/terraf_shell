<?php

namespace Tests\Feature\Api;

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
use Tests\TestCase;

class MaterialCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_material_endpoint_returns_static_oxp_payload_and_homepage_still_returns_published_content(): void
    {
        $material = Material::factory()->published()->create([
            'slug' => 'oyster-shell-material',
            'is_featured' => true,
            'sort_order' => 1,
        ]);
        Material::factory()->create([
            'slug' => 'draft-material',
        ]);

        MaterialSpec::factory()->published()->create([
            'material_id' => $material->id,
            'label' => 'Durability',
            'sort_order' => 1,
        ]);
        MaterialSpec::factory()->create([
            'material_id' => $material->id,
            'label' => 'Draft spec',
        ]);

        MaterialStorySection::factory()->published()->create([
            'material_id' => $material->id,
            'title' => 'Recovery process',
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

        HomeSection::factory()->published()->create([
            'key' => 'hero',
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
            ->assertJsonPath('data.name', 'OXP')
            ->assertJsonPath('data.tagline', "Ocean's Legacy, Crafted with Artisan Tech.")
            ->assertJsonPath('data.origin', 'Recycled oyster shells collected from coastal waste streams')
            ->assertJsonCount(4, 'data.process_steps')
            ->assertJsonCount(6, 'data.properties')
            ->assertJsonCount(5, 'data.certifications')
            ->assertJsonPath('data.models.0.id', 'lite_15')
            ->assertJsonPath('data.colors.1.id', 'forged_ash');

        $this->getJson('/api/materials/oyster-shell-material')
            ->assertOk()
            ->assertJsonPath('data.name', 'OXP');

        $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.tagline', '海洋遗产，以匠人工艺重塑。')
            ->assertJsonPath('data.origin', '从沿海废弃物流中回收的牡蛎壳')
            ->assertJsonPath('data.process_steps.0.title', '收集')
            ->assertJsonPath('data.properties.0.label', '轻量化')
            ->assertJsonPath('data.models.0.name', '1.5 轻盈版')
            ->assertJsonPath('data.colors.0.name', '海骨白');

        $this->getJson('/api/materials?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.tagline', '바다의 유산을 장인의 기술로 다시 빚다.')
            ->assertJsonPath('data.origin', '해안 폐기물 흐름에서 회수한 굴 껍데기')
            ->assertJsonPath('data.process_steps.1.title', '열 정화')
            ->assertJsonPath('data.properties.1.label', '충격 저항성')
            ->assertJsonPath('data.models.0.name', '1.5 라이트')
            ->assertJsonPath('data.colors.1.name', '포지드 애시');

        $this->getJson('/api/home-sections')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'hero');

        $this->getJson('/api/articles')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'lab-update');

        $this->getJson('/api/homepage')
            ->assertOk()
            ->assertJsonCount(1, 'data.home_sections')
            ->assertJsonCount(1, 'data.materials')
            ->assertJsonCount(1, 'data.articles')
            ->assertJsonPath('data.materials.0.slug', 'oyster-shell-material');
    }

    public function test_public_material_endpoint_returns_chinese_material_payload_without_english_fallback_copy(): void
    {
        $response = $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.tagline', '海洋遗产，以匠人工艺重塑。')
            ->assertJsonPath('data.origin', '从沿海废弃物流中回收的牡蛎壳')
            ->assertJsonPath('data.certifications.0.label', '吸水率测试');

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
            'key' => 'hero',
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
            ->assertJsonPath('data.0.title', 'KO section');
    }
}
