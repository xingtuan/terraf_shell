<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_only_returns_matching_approved_posts(): void
    {
        Post::factory()->create([
            'title' => 'The best desk setup for deep work',
            'content' => 'A breakdown of monitor arms, keyboards, and focus tools.',
            'status' => 'approved',
        ]);

        Post::factory()->pending()->create([
            'title' => 'Deep work toolkit draft',
            'content' => 'This pending post should stay hidden from search.',
        ]);

        $response = $this->getJson('/api/search/posts?q=deep work');

        $response->assertOk();

        $titles = collect($response->json('data'))->pluck('title');

        $this->assertCount(1, $titles);
        $this->assertSame('The best desk setup for deep work', $titles->first());
    }

    public function test_search_endpoint_combines_keyword_filters_sorting_and_pagination(): void
    {
        $creator = User::factory()->create([
            'name' => 'Hana Lee',
            'role' => 'creator',
        ]);
        $creator->profile()->update([
            'school_or_company' => 'Pacific Materials Lab',
            'region' => 'Auckland',
        ]);

        $newerPost = Post::factory()->create([
            'user_id' => $creator->id,
            'title' => 'Shell chair concept',
            'content' => 'Premium material concept for hospitality spaces.',
            'created_at' => now()->subHour(),
            'status' => 'approved',
        ]);

        $olderPost = Post::factory()->create([
            'user_id' => $creator->id,
            'title' => 'Shell stool concept',
            'content' => 'Earlier concept exploration for the same creator.',
            'created_at' => now()->subDay(),
            'status' => 'approved',
        ]);

        $wrongRole = User::factory()->admin()->create([
            'name' => 'Hana Staff',
        ]);
        $wrongRole->profile()->update([
            'school_or_company' => 'Pacific Materials Lab',
            'region' => 'Auckland',
        ]);

        Post::factory()->create([
            'user_id' => $wrongRole->id,
            'title' => 'Admin authored concept',
            'content' => 'Should be excluded by creator_role filter.',
            'status' => 'approved',
        ]);

        Post::factory()->pending()->create([
            'user_id' => $creator->id,
            'title' => 'Pending shell concept',
            'content' => 'Should stay hidden from public search results.',
        ]);

        $response = $this->getJson('/api/search/posts?q=hana&school_or_company=Pacific%20Materials%20Lab&region=Auckland&creator_role=creator&sort=latest&per_page=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $newerPost->id);

        $this->assertSame(
            [$newerPost->id],
            collect($response->json('data'))->pluck('id')->all()
        );

        $this->assertNotSame($olderPost->id, $response->json('data.0.id'));
    }

    public function test_admin_search_can_filter_by_status_and_creator_role(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->create([
            'name' => 'Mina Park',
            'role' => 'creator',
        ]);
        $creator->profile()->update([
            'school_or_company' => 'Future Materials Studio',
            'region' => 'Busan',
        ]);

        $pendingPost = Post::factory()->pending()->create([
            'user_id' => $creator->id,
            'title' => 'Shell desk draft',
            'content' => 'Pending concept under review.',
        ]);

        Post::factory()->create([
            'user_id' => $creator->id,
            'title' => 'Shell desk launch',
            'content' => 'Approved concept.',
            'status' => 'approved',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/search/posts?q=shell&status=pending&creator_role=creator')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingPost->id)
            ->assertJsonPath('data.0.status', 'pending');
    }
}
