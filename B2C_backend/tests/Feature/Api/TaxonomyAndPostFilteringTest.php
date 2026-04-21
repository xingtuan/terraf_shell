<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxonomyAndPostFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_category_and_tag_endpoints_return_expected_lists(): void
    {
        $activeCategory = Category::factory()->create([
            'name' => 'Hardware',
            'slug' => 'hardware',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Category::factory()->create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'is_active' => false,
        ]);

        $tag = Tag::factory()->create([
            'name' => 'laravel',
            'slug' => 'laravel',
        ]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeCategory->id)
            ->assertJsonPath('data.0.slug', 'hardware');

        $this->getJson('/api/tags')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $tag->id)
            ->assertJsonPath('data.0.slug', 'laravel');
    }

    public function test_posts_index_supports_category_tag_featured_and_hot_filters(): void
    {
        $category = Category::factory()->create([
            'name' => 'Hardware',
            'slug' => 'hardware',
        ]);
        $otherCategory = Category::factory()->create([
            'name' => 'Software',
            'slug' => 'software',
        ]);
        $tag = Tag::factory()->create([
            'name' => 'laravel',
            'slug' => 'laravel',
        ]);

        $hotPost = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Hot post',
            'likes_count' => 20,
            'comments_count' => 8,
            'favorites_count' => 5,
            'is_featured' => true,
            'status' => 'approved',
        ]);
        $hotPost->tags()->sync([$tag->id]);

        $coldPost = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Cold post',
            'likes_count' => 2,
            'comments_count' => 1,
            'favorites_count' => 0,
            'is_featured' => false,
            'status' => 'approved',
        ]);
        $coldPost->tags()->sync([$tag->id]);

        Post::factory()->create([
            'category_id' => $otherCategory->id,
            'title' => 'Other post',
            'likes_count' => 100,
            'status' => 'approved',
        ]);

        $this->getJson('/api/posts?category=hardware&tag=laravel&sort=hot')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $hotPost->id)
            ->assertJsonPath('data.1.id', $coldPost->id);

        $this->getJson('/api/posts?category=hardware&featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $hotPost->id);
    }

    public function test_posts_index_supports_keyword_and_creator_profile_filters(): void
    {
        $creator = User::factory()->create([
            'name' => 'Ariana Kim',
            'role' => 'creator',
        ]);
        $creator->profile()->update([
            'school_or_company' => 'Pacific Materials Lab',
            'region' => 'Auckland',
        ]);

        $creatorPost = Post::factory()->create([
            'user_id' => $creator->id,
            'title' => 'Oyster shell lounge chair',
            'content' => 'Premium shell-based seating concept.',
            'status' => 'approved',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Ariana Staff',
        ]);
        $admin->profile()->update([
            'school_or_company' => 'Pacific Materials Lab',
            'region' => 'Auckland',
        ]);

        Post::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Staff concept note',
            'content' => 'Should be excluded by creator_role.',
            'status' => 'approved',
        ]);

        $this->getJson('/api/posts?q=ariana&creator_role=creator&school_or_company=Pacific%20Materials%20Lab&region=Auckland')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $creatorPost->id);
    }

    public function test_admin_can_manage_categories_and_tags_over_api(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $categoryResponse = $this->postJson('/api/admin/categories', [
            'name' => 'Productivity',
            'description' => 'Productivity-related posts.',
            'sort_order' => 3,
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'productivity');

        $categoryId = $categoryResponse->json('data.id');

        $this->patchJson("/api/admin/categories/{$categoryId}", [
            'name' => 'Productivity Tools',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Productivity Tools')
            ->assertJsonPath('data.is_active', false);

        $this->getJson('/api/admin/categories?search=Productivity')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $tagResponse = $this->postJson('/api/admin/tags', [
            'name' => 'nextjs',
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'nextjs');

        $tagId = $tagResponse->json('data.id');

        $this->patchJson("/api/admin/tags/{$tagId}", [
            'name' => 'next-js',
        ])->assertOk()
            ->assertJsonPath('data.name', 'next-js');

        $this->deleteJson("/api/admin/categories/{$categoryId}")
            ->assertOk();

        $this->deleteJson("/api/admin/tags/{$tagId}")
            ->assertOk();
    }
}
