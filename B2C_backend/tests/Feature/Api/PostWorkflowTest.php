<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_post_is_pending_until_admin_approves_it(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/posts', [
            'title' => 'A new product worth discussing',
            'content' => 'This is a detailed review of a product I recently tried.',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $postId = $createResponse->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->getJson("/api/posts/{$postId}")
            ->assertNotFound();

        Sanctum::actingAs($user);
        $this->getJson("/api/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.id', $postId);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/posts/{$postId}/status", [
            'status' => 'approved',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->getJson("/api/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_public_posts_index_only_returns_approved_posts(): void
    {
        Post::factory()->create([
            'title' => 'Visible post',
            'status' => 'approved',
        ]);

        Post::factory()->pending()->create([
            'title' => 'Hidden post',
        ]);

        $response = $this->getJson('/api/posts');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');

        $this->assertTrue($titles->contains('Visible post'));
        $this->assertFalse($titles->contains('Hidden post'));
    }
}
