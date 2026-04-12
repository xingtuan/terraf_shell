<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_posts_support_discovery_sorts_and_filters(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-12 12:00:00'));

        $category = Category::factory()->create([
            'name' => 'Hardware',
            'slug' => 'hardware',
        ]);
        $otherCategory = Category::factory()->create([
            'name' => 'Interiors',
            'slug' => 'interiors',
        ]);
        $tag = Tag::factory()->create([
            'name' => 'oyster-shell',
            'slug' => 'oyster-shell',
        ]);
        $otherTag = Tag::factory()->create([
            'name' => 'surface',
            'slug' => 'surface',
        ]);

        $popularCreator = User::factory()->create([
            'name' => 'Alice Creator',
            'username' => 'alicecreator',
        ]);
        $popularCreator->profile->update([
            'school_or_company' => 'Auckland Design Lab',
            'region' => 'Auckland, New Zealand',
        ]);

        $likedCreator = User::factory()->create([
            'name' => 'Brian Creator',
            'username' => 'briancreator',
        ]);
        $likedCreator->profile->update([
            'school_or_company' => 'Seoul Material Lab',
            'region' => 'Seoul, South Korea',
        ]);

        $commentedCreator = User::factory()->create([
            'name' => 'Cara Creator',
            'username' => 'caracreator',
        ]);
        $commentedCreator->profile->update([
            'school_or_company' => 'Busan Design Studio',
            'region' => 'Busan, South Korea',
        ]);

        $trendingCreator = User::factory()->create([
            'name' => 'Tane Creator',
            'username' => 'tanecreator',
        ]);
        $trendingCreator->profile->update([
            'school_or_company' => 'Wellington Circular Lab',
            'region' => 'Wellington, New Zealand',
        ]);

        $latestCreator = User::factory()->create([
            'name' => 'Lina Creator',
            'username' => 'linacreator',
        ]);

        $popularPost = Post::factory()->create([
            'user_id' => $popularCreator->id,
            'category_id' => $category->id,
            'title' => 'Popular concept',
            'status' => 'approved',
            'likes_count' => 15,
            'comments_count' => 12,
            'favorites_count' => 8,
            'engagement_score' => 109,
            'trending_score' => 160,
            'is_featured' => true,
            'featured_at' => now()->subHours(3),
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
            'published_at' => now()->subDays(4),
        ]);
        $popularPost->tags()->sync([$tag->id]);

        $likedPost = Post::factory()->create([
            'user_id' => $likedCreator->id,
            'category_id' => $category->id,
            'title' => 'Most liked concept',
            'status' => 'approved',
            'likes_count' => 30,
            'comments_count' => 2,
            'favorites_count' => 0,
            'engagement_score' => 98,
            'trending_score' => 120,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
            'published_at' => now()->subDays(3),
        ]);
        $likedPost->tags()->sync([$tag->id]);

        $commentedPost = Post::factory()->create([
            'user_id' => $commentedCreator->id,
            'category_id' => $category->id,
            'title' => 'Most discussed concept',
            'status' => 'approved',
            'likes_count' => 5,
            'comments_count' => 18,
            'favorites_count' => 1,
            'engagement_score' => 89,
            'trending_score' => 130,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
            'published_at' => now()->subDays(2),
        ]);
        $commentedPost->tags()->sync([$otherTag->id]);

        $trendingPost = Post::factory()->create([
            'user_id' => $trendingCreator->id,
            'category_id' => $otherCategory->id,
            'title' => 'Trending concept',
            'status' => 'approved',
            'likes_count' => 2,
            'comments_count' => 2,
            'favorites_count' => 2,
            'engagement_score' => 18,
            'trending_score' => 420,
            'created_at' => now()->subHours(30),
            'updated_at' => now()->subHours(30),
            'published_at' => now()->subHours(30),
        ]);
        $trendingPost->tags()->sync([$tag->id]);

        $latestPost = Post::factory()->create([
            'user_id' => $latestCreator->id,
            'category_id' => $category->id,
            'title' => 'Latest concept',
            'status' => 'approved',
            'likes_count' => 1,
            'comments_count' => 1,
            'favorites_count' => 1,
            'engagement_score' => 9,
            'trending_score' => 100,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
            'published_at' => now()->subHour(),
        ]);
        $latestPost->tags()->sync([$tag->id]);

        Post::factory()->pending()->create([
            'category_id' => $category->id,
            'title' => 'Pending concept',
        ]);

        $this->getJson('/api/posts?sort=latest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $latestPost->id);

        $this->getJson('/api/posts?sort=hot')
            ->assertOk()
            ->assertJsonPath('data.0.id', $popularPost->id);

        $this->getJson('/api/posts?sort=popular')
            ->assertOk()
            ->assertJsonPath('data.0.id', $popularPost->id);

        $this->getJson('/api/posts?sort=trending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $trendingPost->id);

        $this->getJson('/api/posts?sort=most_liked')
            ->assertOk()
            ->assertJsonPath('data.0.id', $likedPost->id);

        $this->getJson('/api/posts?sort=most_discussed')
            ->assertOk()
            ->assertJsonPath('data.0.id', $commentedPost->id);

        $this->getJson('/api/posts?creator=alicecreator&school_or_company=Auckland%20Design&region=Auckland&category=hardware&tag=oyster-shell&featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $popularPost->id)
            ->assertJsonPath('data.0.engagement_score', 109)
            ->assertJsonPath('data.0.trending_score', 160);
    }

    public function test_trending_formula_is_testable_and_admin_can_feature_concepts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-12 12:00:00'));

        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $creator = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $creator->id,
            'title' => 'Ranked concept',
            'status' => 'approved',
            'likes_count' => 1,
            'comments_count' => 0,
            'favorites_count' => 0,
        ]);
        $post->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'published_at' => now()->subDay(),
        ])->saveQuietly();

        $pendingPost = Post::factory()->pending()->create([
            'user_id' => $creator->id,
            'title' => 'Pending discovery concept',
        ]);

        DB::table('post_likes')->insert([
            [
                'post_id' => $post->id,
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
        ]);
        $rankingService = $this->app->make(PostRankingService::class);

        $baselinePost = $rankingService->refreshScores($post->fresh());
        $baselineTrendingScore = (int) $baselinePost->trending_score;
        $this->assertSame(3, (int) $baselinePost->engagement_score);

        DB::table('post_likes')->insert([
            [
                'post_id' => $post->id,
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
        ]);
        $post->increment('likes_count');

        $afterLike = $rankingService->refreshScores($post->fresh());
        $this->assertSame(6, (int) $afterLike->engagement_score);
        $this->assertSame($baselineTrendingScore + 30, (int) $afterLike->trending_score);

        DB::table('comments')->insert([
            [
                'post_id' => $post->id,
                'user_id' => User::factory()->create()->id,
                'parent_id' => null,
                'content' => 'Recent approved comment',
                'status' => 'approved',
                'likes_count' => 0,
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
        ]);
        $post->increment('comments_count');

        $afterComment = $rankingService->refreshScores($post->fresh());
        $this->assertSame(10, (int) $afterComment->engagement_score);
        $this->assertSame($baselineTrendingScore + 70, (int) $afterComment->trending_score);

        DB::table('favorites')->insert([
            [
                'post_id' => $post->id,
                'user_id' => User::factory()->create()->id,
                'created_at' => now()->subHours(10),
                'updated_at' => now()->subHours(10),
            ],
        ]);
        $post->increment('favorites_count');

        $rankedPost = $rankingService->refreshScores($post->fresh());
        $this->assertSame(12, (int) $rankedPost->engagement_score);
        $this->assertSame($baselineTrendingScore + 90, (int) $rankedPost->trending_score);

        Sanctum::actingAs($moderator);
        $this->getJson('/api/posts?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingPost->id);

        $this->getJson('/api/admin/posts/ranking-formula')
            ->assertOk()
            ->assertJsonPath('data.window_days', 7)
            ->assertJsonPath('data.recency_boost_max_hours', 168)
            ->assertJsonPath('data.engagement_score', 'likes_count * 3 + comments_count * 4 + favorites_count * 2');

        $this->patchJson("/api/admin/posts/{$rankedPost->id}/feature", [
            'is_featured' => true,
        ])->assertForbidden();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/posts/{$rankedPost->id}/feature", [
            'is_featured' => true,
            'reason' => 'Featured for the homepage leaderboard.',
        ])
            ->assertOk()
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.featured_by', $admin->id);

        $this->getJson('/api/posts?featured=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $rankedPost->id);
    }
}
