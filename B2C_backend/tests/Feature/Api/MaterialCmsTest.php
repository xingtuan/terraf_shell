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

    public function test_public_material_and_homepage_endpoints_only_return_published_content(): void
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
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'oyster-shell-material');

        $this->getJson('/api/materials/oyster-shell-material')
            ->assertOk()
            ->assertJsonPath('data.slug', 'oyster-shell-material')
            ->assertJsonCount(1, 'data.specs')
            ->assertJsonCount(1, 'data.story_sections')
            ->assertJsonCount(1, 'data.applications');

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

        $this->getJson('/api/materials/premium-oyster-shell')
            ->assertOk()
            ->assertJsonCount(1, 'data.specs')
            ->assertJsonCount(1, 'data.story_sections')
            ->assertJsonCount(1, 'data.applications');
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
}
