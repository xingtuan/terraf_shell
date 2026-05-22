<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Services\Storage\StorageManagerService;
use App\Support\StorageUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_media_url_is_publicly_resolvable_on_local_disks(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $uploadResponse = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('render.png'),
            'category' => 'community',
        ], [
            'Accept' => 'application/json',
        ]);

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $path = (string) $uploadResponse->json('data.path');
        $url = (string) $uploadResponse->json('data.url');

        Storage::disk('public')->assertExists($path);
        $this->assertSame(StorageUrl::publicResolve($path, 'public'), $url);
        $this->assertSame('/storage/'.$path, parse_url($url, PHP_URL_PATH));

        $proxyUrl = route('media.files.show', [
            'disk' => 'public',
            'path' => $path,
        ]);

        $response = $this->get($proxyUrl)
            ->assertOk();

        $cacheControl = (string) $response->headers->get('cache-control');

        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
    }

    public function test_authenticated_user_can_upload_and_delete_media_files(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $uploadResponse = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->create('concept-deck.pdf', 120, 'application/pdf'),
            'category' => 'documents',
        ], [
            'Accept' => 'application/json',
        ]);

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'documents')
            ->assertJsonPath('data.mime', 'application/pdf')
            ->assertJsonPath('data.original_name', 'concept-deck.pdf');

        $path = (string) $uploadResponse->json('data.path');

        $this->assertMatchesRegularExpression(
            '#^documents/documents/\d{4}/\d{2}/[0-9a-f\-]+\.pdf$#',
            $path
        );

        Storage::disk('public')->assertExists($path);

        $this->assertDatabaseHas('media_files', [
            'user_id' => $user->id,
            'path' => $path,
            'type' => 'documents',
            'mime_type' => 'application/pdf',
            'category' => 'documents',
        ]);

        $this->deleteJson('/api/media', [
            'path' => $path,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('media_files', [
            'path' => $path,
        ]);
    }

    public function test_runtime_local_storage_setting_writes_to_public_disk_and_survives_driver_switch_on_delete(): void
    {
        Config::set('community.uploads.disk', 'azure');
        Config::set('filesystems.default', 'azure');
        Storage::fake('public');

        $settings = app(SettingsService::class);
        $settings->set('storage.default_driver', 'local', ['type' => 'string']);
        $settings->set('storage.local.disk', 'public', ['type' => 'string']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $uploadResponse = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('local-runtime.png'),
            'category' => 'community',
        ], [
            'Accept' => 'application/json',
        ]);

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.disk', 'public');

        $path = (string) $uploadResponse->json('data.path');
        $url = (string) $uploadResponse->json('data.url');

        Storage::disk('public')->assertExists($path);
        $this->assertSame('/storage/'.$path, parse_url($url, PHP_URL_PATH));
        $this->assertDatabaseHas('media_files', [
            'user_id' => $user->id,
            'path' => $path,
            'disk' => 'public',
        ]);

        $settings->set('storage.default_driver', 'azure', ['type' => 'string']);

        $this->deleteJson('/api/media', [
            'path' => $path,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('media_files', [
            'path' => $path,
        ]);
    }

    public function test_failed_storage_write_returns_error_without_creating_media_record(): void
    {
        $this->instance(StorageManagerService::class, new class(app(SettingsService::class)) extends StorageManagerService
        {
            public function disk(): string
            {
                return 'public';
            }

            public function put(string $path, string $contents, array $options = []): bool
            {
                return false;
            }
        });

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('broken.png'),
            'category' => 'community',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('api.media.local_write_failed', ['disk' => 'public']));

        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_user_cannot_delete_another_users_media_file(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($owner);

        $uploadResponse = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('render.png'),
            'category' => 'designs',
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $path = (string) $uploadResponse->json('data.path');

        Sanctum::actingAs($otherUser);

        $this->deleteJson('/api/media', [
            'path' => $path,
        ])->assertForbidden();

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('media_files', [
            'user_id' => $owner->id,
            'path' => $path,
        ]);
    }

    public function test_guest_upload_route_exists_but_runtime_setting_can_disable_it(): void
    {
        app(SettingsService::class)->set('community.allow_guest_upload', false, [
            'type' => 'boolean',
        ]);

        $this->postJson('/api/media/upload/guest', [
            'file' => UploadedFile::fake()->image('guest.png'),
            'category' => 'community',
        ])->assertForbidden();
    }

    public function test_guest_upload_route_allows_upload_when_runtime_setting_is_enabled(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');
        app(SettingsService::class)->set('community.allow_guest_upload', true, [
            'type' => 'boolean',
        ]);

        $response = $this->post('/api/media/upload/guest', [
            'file' => UploadedFile::fake()->image('guest.png'),
            'category' => 'community',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $path = (string) $response->json('data.path');

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('media_files', [
            'path' => $path,
            'user_id' => null,
        ]);
    }

    public function test_generic_upload_rejects_executable_like_and_oversized_files(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->create('payload.php', 1, 'application/x-php'),
            'category' => 'documents',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml'),
            'category' => 'community-cover',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('oversized.png')->size(6000),
            'category' => 'community-cover',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_community_cover_upload_uses_image_category_not_attachment_category(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('cover.png'),
            'category' => 'community-cover',
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('data.type', 'images');

        $this->assertMatchesRegularExpression(
            '#^images/community-cover/\d{4}/\d{2}/[0-9a-f\-]+\.png$#',
            (string) $response->json('data.path')
        );
    }
}
