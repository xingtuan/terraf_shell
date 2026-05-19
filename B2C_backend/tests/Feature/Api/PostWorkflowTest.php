<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use App\Support\StorageUrl;
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
        $indexResponse->assertJsonPath('data.0.content_json.type', 'doc');
    }

    public function test_post_update_can_store_tiptap_content_without_stripping_marks(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'status' => 'approved',
            'content' => 'Original rich text content for the update test.',
            'content_json' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Original rich text content for the update test.'],
                        ],
                    ],
                ],
            ],
        ]);

        Sanctum::actingAs($admin);

        $contentJson = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Updated bold phrase',
                            'marks' => [['type' => 'bold']],
                        ],
                        ['type' => 'text', 'text' => ' with a '],
                        [
                            'type' => 'text',
                            'text' => 'reference link',
                            'marks' => [
                                [
                                    'type' => 'link',
                                    'attrs' => [
                                        'href' => 'https://example.com/reference',
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer nofollow',
                                    ],
                                ],
                            ],
                        ],
                        ['type' => 'text', 'text' => ' for the community.'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated rich editor concept',
            'content' => 'Updated bold phrase with a reference link for the community.',
            'content_json' => $contentJson,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.content', 'Updated bold phrase with a reference link for the community.')
            ->assertJsonPath('data.content_json.content.0.content.0.marks.0.type', 'bold')
            ->assertJsonPath('data.content_json.content.0.content.2.marks.0.type', 'link')
            ->assertJsonPath('data.content_json.content.0.content.2.marks.0.attrs.href', 'https://example.com/reference');

        $post->refresh();

        $this->assertSame('bold', $post->content_json['content'][0]['content'][0]['marks'][0]['type']);
        $this->assertSame('link', $post->content_json['content'][0]['content'][2]['marks'][0]['type']);
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
            'cover_image_disk' => 'public',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/posts/{$post->id}", [
            'cover_image_url' => 'https://example.com/new-cover.jpg',
            'cover_image_path' => 'images/community/2026/04/new-cover.jpg',
            'cover_image_disk' => 'public',
        ])
            ->assertOk()
            ->assertJsonPath('data.cover_image_url', StorageUrl::publicResolve('images/community/2026/04/new-cover.jpg', 'public'))
            ->assertJsonPath('data.cover_image_path', 'images/community/2026/04/new-cover.jpg');

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_post_cover_uses_stored_disk_after_active_storage_switch(): void
    {
        Storage::fake('public');
        Storage::fake('azure');

        $admin = User::factory()->admin()->create();
        $path = 'images/community/2026/05/local-cover.png';
        Storage::disk('public')->put($path, 'cover');

        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'cover_image_path' => $path,
            'cover_image_disk' => 'public',
            'cover_image_url' => StorageUrl::publicResolve($path, 'public'),
            'status' => 'approved',
        ]);

        Config::set('community.uploads.disk', 'azure');
        Sanctum::actingAs($admin);

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.cover_image_disk', 'public')
            ->assertJsonPath('data.cover_image_url', StorageUrl::resolve($path, 'public'));
    }
}
