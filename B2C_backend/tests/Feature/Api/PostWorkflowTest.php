<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
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

    public function test_post_creation_can_store_tiptap_content_and_derive_search_fields(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $contentJson = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [
                        ['type' => 'text', 'text' => 'Oyster shell stool'],
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'This concept uses reclaimed shell material for premium seating prototypes.'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->postJson('/api/posts', [
            'title' => 'Rich editor concept',
            'content_json' => $contentJson,
            'tags' => ['shell', 'furniture'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.content', 'Oyster shell stool This concept uses reclaimed shell material for premium seating prototypes.')
            ->assertJsonPath('data.excerpt', 'Oyster shell stool This concept uses reclaimed shell material for premium seating prototypes.')
            ->assertJsonPath('data.reading_time', 1)
            ->assertJsonPath('data.content_json.type', 'doc');

        $postId = $response->json('data.id');

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'content' => 'Oyster shell stool This concept uses reclaimed shell material for premium seating prototypes.',
            'reading_time' => 1,
        ]);

        $indexResponse = $this->getJson('/api/posts');
        $this->assertArrayNotHasKey('content_json', $indexResponse->json('data.0'));
    }

    public function test_post_update_deletes_replaced_cover_image_after_commit(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        $oldPath = 'images/community/2026/04/old-cover.jpg';
        Storage::disk('public')->put($oldPath, 'cover');

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'cover_image_url' => 'https://example.com/old-cover.jpg',
            'cover_image_path' => $oldPath,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/posts/{$post->id}", [
            'cover_image_url' => 'https://example.com/new-cover.jpg',
            'cover_image_path' => 'images/community/2026/04/new-cover.jpg',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.cover_image_path', 'images/community/2026/04/new-cover.jpg');

        $this->assertStringEndsWith(
            '/media/files/public/images/community/2026/04/new-cover.jpg',
            (string) $response->json('data.cover_image_url')
        );

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_post_owner_and_admin_can_update_but_other_users_cannot(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Original title',
            'content' => 'Original content for authorization testing.',
            'status' => 'approved',
        ]);

        Sanctum::actingAs($otherUser);

        $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Unauthorized update',
            'content' => 'This should not be allowed for another user.',
        ])->assertForbidden();

        Sanctum::actingAs($owner);

        $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Owner update',
            'content' => 'The owner can update their own post content safely.',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Owner update');

        Sanctum::actingAs($admin);

        $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Admin update',
            'content' => 'An admin can update any post through the same endpoint.',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Admin update');
    }

    public function test_post_delete_removes_cover_image_after_commit(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        $coverPath = 'images/community/2026/04/delete-cover.jpg';
        Storage::disk('public')->put($coverPath, 'cover');

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'cover_image_url' => 'https://example.com/delete-cover.jpg',
            'cover_image_path' => $coverPath,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/posts/{$post->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing($coverPath);
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }
}
