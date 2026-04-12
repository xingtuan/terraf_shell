<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
