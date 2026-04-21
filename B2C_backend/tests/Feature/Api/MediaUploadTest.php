<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_and_delete_media_files(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $uploadResponse = $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->create('concept-deck.pdf', 120, 'application/pdf'),
            'category' => 'community',
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
            '#^documents/community/\d{4}/\d{2}/[0-9a-f\-]+\.pdf$#',
            $path
        );

        Storage::disk('public')->assertExists($path);

        $this->assertDatabaseHas('media_files', [
            'user_id' => $user->id,
            'path' => $path,
            'type' => 'documents',
            'mime_type' => 'application/pdf',
            'category' => 'community',
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
}
