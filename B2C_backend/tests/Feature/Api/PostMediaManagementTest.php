<?php

namespace Tests\Feature\Api;

use App\Models\IdeaMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostMediaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_submit_post_with_mixed_media_and_legacy_images(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/posts', [
            'title' => 'Oyster shell seating concept',
            'content' => 'A premium material concept with sketches, renders, and a supporting deck.',
            'images' => [
                UploadedFile::fake()->create('sketch.jpg', 150, 'image/jpeg'),
            ],
            'image_alts' => ['Initial oyster shell sketch'],
            'attachments' => [
                UploadedFile::fake()->create('render.png', 180, 'image/png'),
                UploadedFile::fake()->create('deck.pdf', 240, 'application/pdf'),
            ],
            'attachment_titles' => ['Render board', 'Presentation deck'],
            'attachment_alts' => ['Rendered material board', null],
            'attachment_kinds' => ['render_image', 'pdf_presentation'],
            'model_3d_links' => [
                [
                    'url' => 'https://sketchfab.com/models/oyster-shell-stool',
                    'title' => '3D exploration',
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(2, 'data.images')
            ->assertJsonCount(4, 'data.media');

        $media = collect($response->json('data.media'));

        $this->assertTrue($media->contains(
            fn (array $item): bool => $item['media_type'] === 'document'
                && $item['kind'] === 'pdf_presentation'
        ));
        $this->assertTrue($media->contains(
            fn (array $item): bool => $item['media_type'] === 'external_3d'
                && $item['external_url'] === 'https://sketchfab.com/models/oyster-shell-stool'
        ));
        $this->assertTrue(
            $media
                ->where('media_type', 'image')
                ->every(fn (array $item): bool => filled($item['preview_url']) && filled($item['thumbnail_url']))
        );

        $this->assertDatabaseCount('idea_media', 4);
    }

    public function test_post_update_can_remove_and_replace_media_without_breaking_legacy_images(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $createResponse = $this->post('/api/posts', [
            'title' => 'Oyster shell lamp concept',
            'content' => 'Original concept package.',
            'images' => [
                UploadedFile::fake()->create('concept.jpg', 120, 'image/jpeg'),
            ],
            'attachments' => [
                UploadedFile::fake()->create('spec.pdf', 150, 'application/pdf'),
            ],
            'attachment_kinds' => ['spec_sheet'],
            'model_3d_links' => [
                [
                    'url' => 'https://sketchfab.com/models/oyster-shell-lamp',
                    'title' => 'Lamp model',
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $postId = $createResponse->json('data.id');
        $media = collect($createResponse->json('data.media'));
        $imageId = (int) $media->firstWhere('media_type', 'image')['id'];
        $documentId = (int) $media->firstWhere('media_type', 'document')['id'];
        $externalId = (int) $media->firstWhere('media_type', 'external_3d')['id'];
        $oldDocumentPath = (string) IdeaMedia::query()->findOrFail($documentId)->path;

        $updateResponse = $this->patch("/api/posts/{$postId}", [
            'content' => 'Updated concept package with a revised render.',
            'remove_image_ids' => [$imageId],
            'attachments' => [
                UploadedFile::fake()->create('updated-render.png', 160, 'image/png'),
            ],
            'attachment_titles' => ['Updated render'],
            'attachment_alts' => ['Updated oyster shell render'],
            'attachment_kinds' => ['render_image'],
            'replace_media' => [
                [
                    'id' => $documentId,
                    'file' => UploadedFile::fake()->create('revised-spec.pdf', 170, 'application/pdf'),
                    'title' => 'Revised spec sheet',
                    'kind' => 'spec_sheet',
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(1, 'data.images')
            ->assertJsonCount(3, 'data.media');

        $updatedDocument = IdeaMedia::query()->findOrFail($documentId);

        $this->assertSame('spec_sheet', $updatedDocument->kind);
        $this->assertNotSame($oldDocumentPath, $updatedDocument->path);
        Storage::disk('public')->assertMissing($oldDocumentPath);

        $this->assertDatabaseMissing('idea_media', [
            'id' => $imageId,
        ]);
        $this->assertDatabaseHas('idea_media', [
            'id' => $externalId,
            'media_type' => 'external_3d',
        ]);
    }

    public function test_post_update_accepts_multipart_method_override_with_safe_attachments(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $createResponse = $this->post('/api/posts', [
            'title' => 'Community attachment update flow',
            'content' => 'Original post content with enough text to satisfy validation.',
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $postId = $createResponse->json('data.id');

        $updateResponse = $this->post("/api/posts/{$postId}", [
            '_method' => 'PUT',
            'content' => 'Updated post content with a render preview and a supporting document.',
            'attachments' => [
                UploadedFile::fake()->create('concept-preview.png', 120, 'image/png'),
                UploadedFile::fake()->create(
                    'manufacturing-notes.docx',
                    80,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonCount(2, 'data.media');

        $this->assertDatabaseHas('idea_media', [
            'post_id' => $postId,
            'original_name' => 'concept-preview.png',
            'media_type' => 'image',
        ]);
        $this->assertDatabaseHas('idea_media', [
            'post_id' => $postId,
            'original_name' => 'manufacturing-notes.docx',
            'media_type' => 'document',
        ]);
    }

    public function test_post_media_validation_rejects_invalid_types_and_oversized_files(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/posts', [
            'title' => 'Invalid attachment concept',
            'content' => 'Should fail because the attachment type is unsupported.',
            'attachments' => [
                UploadedFile::fake()->create('payload.exe', 50, 'application/octet-stream'),
            ],
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);

        $this->post('/api/posts', [
            'title' => 'Oversized attachment concept',
            'content' => 'Should fail because the uploaded document is too large.',
            'attachments' => [
                UploadedFile::fake()->create('oversized-deck.pdf', 12000, 'application/pdf'),
            ],
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);
    }

    public function test_creator_can_submit_safe_non_image_files_and_download_counts_increment(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $createResponse = $this->post('/api/posts', [
            'title' => 'Mixed attachment package',
            'content' => 'A concept package with notes, a zipped source archive, and a printable brief.',
            'attachments' => [
                UploadedFile::fake()->create('brief.txt', 8, 'text/plain'),
                UploadedFile::fake()->create('source-package.zip', 32, 'application/zip'),
            ],
            'attachment_titles' => ['Brief', 'Source package'],
            'attachment_kinds' => ['reference_document', 'reference_document'],
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $document = collect($createResponse->json('data.media'))
            ->first(fn (array $item): bool => ($item['title'] ?? null) === 'Brief');

        $this->assertNotNull($document);
        $this->assertSame(0, $document['download_count']);
        $this->assertNotEmpty($document['download_url']);

        $downloadResponse = $this->get($document['download_url'], [
            'Accept' => 'application/json',
        ]);

        $downloadResponse
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('idea_media', [
            'id' => $document['id'],
            'download_count' => 1,
        ]);
    }
}
