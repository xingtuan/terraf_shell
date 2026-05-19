<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminResourcesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_only_resource_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/enquiries')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/home-sections')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/materials')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/materials/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/material-specs')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/material-story-sections')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/material-applications')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/articles')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/product-categories')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/product-images')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/carts')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/addresses')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/b2-b-leads')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/funding-campaigns')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/media-files')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/shipping-settings')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/system-handover-readiness')
            ->assertOk();
    }

    public function test_moderator_can_access_staff_resources_but_not_admin_only_pages(): void
    {
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->actingAs($moderator)
            ->get('/admin/enquiries')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/posts')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/comments')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/reports')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/user-violations')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/idea-media')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/user-notifications')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/home-sections')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/materials')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/material-specs')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/material-story-sections')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/material-applications')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/articles')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/product-categories')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/products')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/product-images')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/carts')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/addresses')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/b2-b-leads')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/funding-campaigns')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/media-files')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/shipping-settings')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/system-handover-readiness')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get("/admin/users/{$user->getKey()}/edit")
            ->assertForbidden();
    }

    public function test_non_staff_roles_cannot_access_admin_resource_pages(): void
    {
        $creator = User::factory()->create();
        $smePartner = User::factory()->smePartner()->create();

        $this->actingAs($creator)
            ->get('/admin/posts')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/admin/materials')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/admin/products')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/admin/enquiries')
            ->assertForbidden();

        $this->actingAs($smePartner)
            ->get('/admin/comments')
            ->assertForbidden();
    }

    public function test_post_edit_page_handles_json_like_plain_content_without_tiptap_crash(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'content' => json_encode([
                'type' => 'doc',
                'content' => [0.15],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($admin)
            ->get("/admin/posts/{$post->getKey()}/edit")
            ->assertOk();
    }

    public function test_post_admin_pages_show_local_cover_and_inline_images_after_storage_switch(): void
    {
        Storage::fake('public');
        Storage::fake('azure');
        Config::set('community.uploads.disk', 'public');

        $admin = User::factory()->admin()->create();
        $coverPath = 'images/community/2026/05/cover.png';
        $inlinePath = 'images/community/2026/05/inline.png';
        $attachmentPath = 'images/community/2026/05/attachment.png';

        Storage::disk('public')->put($coverPath, 'cover');
        Storage::disk('public')->put($inlinePath, 'inline');
        Storage::disk('public')->put($attachmentPath, 'attachment');

        $post = Post::factory()->create([
            'cover_image_path' => $coverPath,
            'cover_image_disk' => 'public',
            'content_json' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => "/media/files/public/{$inlinePath}",
                        ],
                    ],
                ],
            ],
        ]);

        $post->media()->create([
            'source_type' => 'upload',
            'media_type' => 'image',
            'kind' => 'concept_image',
            'disk' => 'public',
            'path' => $attachmentPath,
            'original_name' => 'attachment.png',
            'file_name' => 'attachment.png',
            'extension' => 'png',
            'mime_type' => 'image/png',
            'size_bytes' => 10,
            'sort_order' => 0,
        ]);

        Config::set('community.uploads.disk', 'azure');

        $this->actingAs($admin)
            ->get("/admin/posts/{$post->getKey()}")
            ->assertOk()
            ->assertSee('inline.png');

        $this->actingAs($admin)
            ->get("/admin/posts/{$post->getKey()}/edit")
            ->assertOk()
            ->assertSee('inline.png');
    }
}
