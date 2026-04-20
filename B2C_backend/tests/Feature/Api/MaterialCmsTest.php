<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Certification;
use App\Models\HomeSection;
use App\Models\Material;
use App\Models\MaterialProperty;
use App\Models\MaterialApplication;
use App\Models\MaterialSpec;
use App\Models\MaterialStorySection;
use App\Models\ProcessStep;
use App\Models\SiteSection;
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

    public function test_public_content_endpoints_return_database_driven_shellfin_payloads_and_homepage_still_returns_published_content(): void
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

        SiteSection::query()->create([
            'page' => 'home',
            'section' => 'hero',
            'locale' => 'en',
            'title' => 'Updated hero title',
            'subtitle' => 'Updated hero subtitle',
            'cta_label' => 'Explore Collection',
            'cta_url' => '/store',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        SiteSection::query()->create([
            'page' => 'material',
            'section' => 'hero',
            'locale' => 'en',
            'title' => 'The Material',
            'subtitle' => 'Shellfin is not ceramic. It is something entirely new.',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        SiteSection::query()->create([
            'page' => 'material',
            'section' => 'origin',
            'locale' => 'en',
            'title' => 'Born from the Sea',
            'body' => 'Oyster shells collected from coastal waste streams.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        foreach ([
            [
                'key' => 'weight',
                'label' => 'Lightweight',
                'value' => '1.5-1.6 specific gravity',
                'comparison' => '35% lighter than ceramic (2.4)',
                'sort_order' => 1,
            ],
            [
                'key' => 'strength',
                'label' => 'Impact Resistant',
                'value' => 'Unbreakable integrity',
                'comparison' => 'Overcomes ceramic chipping & cracking',
                'sort_order' => 2,
            ],
            [
                'key' => 'absorption',
                'label' => 'Zero Absorption',
                'value' => '0.00% water absorption',
                'comparison' => 'No odour, no staining, no bacteria',
                'sort_order' => 3,
            ],
            [
                'key' => 'antibacterial',
                'label' => 'Natural Antibacterial',
                'value' => 'Weak alkaline inhibition',
                'comparison' => 'No artificial coatings needed',
                'sort_order' => 4,
            ],
            [
                'key' => 'grip',
                'label' => 'Mineral Grip',
                'value' => 'Fine mineral texture surface',
                'comparison' => 'Non-slip even when wet with soap',
                'sort_order' => 5,
            ],
            [
                'key' => 'otr',
                'label' => 'Selective Flow',
                'value' => 'OTR 500 cc/m2/day',
                'comparison' => 'Breathable yet moisture-blocking',
                'sort_order' => 6,
            ],
        ] as $property) {
            MaterialProperty::query()->create([
                ...$property,
                'locale' => 'en',
                'is_active' => true,
            ]);
        }

        foreach ([
            ['step_number' => 1, 'title' => 'Collection', 'body' => 'Step 1'],
            ['step_number' => 2, 'title' => 'Thermal Purification', 'body' => 'Step 2'],
            ['step_number' => 3, 'title' => 'Pelletisation', 'body' => 'Step 3'],
            ['step_number' => 4, 'title' => 'Compression Moulding', 'body' => 'Step 4'],
        ] as $step) {
            ProcessStep::query()->create([
                ...$step,
                'locale' => 'en',
                'is_active' => true,
            ]);
        }

        foreach ([
            ['key' => 'absorption', 'label' => 'Water Absorption Test', 'value' => '0.00%', 'sort_order' => 1],
            ['key' => 'toxicity', 'label' => 'Toxicity Test', 'value' => 'Zero heavy metals, zero microplastics', 'sort_order' => 2],
            ['key' => 'acid', 'label' => 'Acid/Corrosion Resistance', 'value' => 'No surface degradation', 'sort_order' => 3],
            ['key' => 'fire', 'label' => 'Non-Toxic Fireproof', 'value' => 'Non-flammable, zero toxic gas', 'sort_order' => 4],
            ['key' => 'otr', 'label' => 'OTR Data', 'value' => '500 cc/m2/day certified', 'sort_order' => 5],
        ] as $certification) {
            Certification::query()->create([
                ...$certification,
                'locale' => 'en',
                'is_active' => true,
            ]);
        }

        $this->getJson('/api/content/home')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hero.title', 'Updated hero title')
            ->assertJsonPath('data.hero.subtitle', 'Updated hero subtitle');

        $this->getJson('/api/materials')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sections.hero.title', 'The Material')
            ->assertJsonPath('data.sections.origin.title', 'Born from the Sea')
            ->assertJsonCount(4, 'data.process')
            ->assertJsonCount(6, 'data.properties')
            ->assertJsonCount(5, 'data.certifications')
            ->assertJsonPath('data.properties.0.key', 'weight')
            ->assertJsonPath('data.process.3.step_number', 4);

        $this->getJson('/api/materials/oyster-shell-material')
            ->assertOk()
            ->assertJsonPath('data.sections.hero.title', 'The Material');

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

    public function test_new_content_endpoints_resolve_requested_locale_when_translations_exist(): void
    {
        SiteSection::query()->create([
            'page' => 'home',
            'section' => 'hero',
            'locale' => 'en',
            'title' => 'English hero',
            'is_active' => true,
        ]);
        SiteSection::query()->create([
            'page' => 'home',
            'section' => 'hero',
            'locale' => 'ko',
            'title' => '[KO] Hero',
            'is_active' => true,
        ]);
        MaterialProperty::query()->create([
            'key' => 'weight',
            'locale' => 'en',
            'label' => 'Lightweight',
            'value' => '1.5-1.6 specific gravity',
            'comparison' => '35% lighter than ceramic (2.4)',
            'is_active' => true,
        ]);
        MaterialProperty::query()->create([
            'key' => 'weight',
            'locale' => 'zh',
            'label' => '[ZH] Lightweight',
            'value' => '[ZH] 1.5-1.6 specific gravity',
            'comparison' => '[ZH] 35% lighter than ceramic (2.4)',
            'is_active' => true,
        ]);

        $this->getJson('/api/content/home?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.hero.title', '[KO] Hero');

        $this->getJson('/api/materials?locale=zh')
            ->assertOk()
            ->assertJsonPath('data.properties.0.label', '[ZH] Lightweight');
    }
}
