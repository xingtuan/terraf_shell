<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use App\Services\CommunitySettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunitySettingsRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_settings_service_reads_latest_runtime_values(): void
    {
        $settings = app(SettingsService::class);

        $settings->setMany([
            'community.allow_guest_upload' => ['value' => true, 'type' => 'boolean'],
            'community.max_files' => ['value' => 3, 'type' => 'integer'],
            'community.max_file_size_kb' => ['value' => 256, 'type' => 'integer'],
            'community.allowed_extensions' => ['value' => ".PNG\npdf", 'type' => 'string'],
            'community.max_external_links' => ['value' => 0, 'type' => 'integer'],
            'community.submission_policy' => ['value' => 'auto_publish', 'type' => 'string'],
            'community.sensitive_words_enabled' => ['value' => true, 'type' => 'boolean'],
            'community.sensitive_words' => ['value' => 'alpha,beta', 'type' => 'string'],
            'community.default_funding_support_button_text' => ['value' => 'Back this idea', 'type' => 'string'],
        ]);

        $communitySettings = app(CommunitySettingsService::class);

        $this->assertTrue($communitySettings->allowGuestUpload());
        $this->assertSame(3, $communitySettings->maxFiles());
        $this->assertSame(256, $communitySettings->maxFileSizeKb());
        $this->assertSame(['png', 'pdf'], $communitySettings->allowedExtensions());
        $this->assertSame(0, $communitySettings->maxExternalLinks());
        $this->assertSame('all_auto_approve', $communitySettings->submissionPolicy());
        $this->assertTrue($communitySettings->sensitiveWordsEnabled());
        $this->assertSame(['alpha', 'beta'], $communitySettings->sensitiveWords());
        $this->assertSame('Back this idea', $communitySettings->defaultFundingSupportButtonText());
    }

    public function test_guest_upload_respects_runtime_setting(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');
        $settings = app(SettingsService::class);

        $settings->set('community.allow_guest_upload', false, ['type' => 'boolean']);

        $this->postJson('/api/media/upload/guest', [
            'file' => UploadedFile::fake()->image('guest.png'),
            'category' => 'community',
        ])->assertForbidden();

        $settings->set('community.allow_guest_upload', true, ['type' => 'boolean']);

        $this->post('/api/media/upload/guest', [
            'file' => UploadedFile::fake()->image('guest.png'),
            'category' => 'community',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_upload_limits_use_runtime_size_and_extension_settings(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $settings = app(SettingsService::class);

        $settings->set('community.max_file_size_kb', 1, ['type' => 'integer']);

        $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('large.png')->size(20),
            'category' => 'community',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $settings->set('community.max_file_size_kb', 10240, ['type' => 'integer']);
        $settings->set('community.allowed_extensions', ['jpg'], ['type' => 'json']);

        $this->post('/api/media/upload', [
            'file' => UploadedFile::fake()->image('blocked.png'),
            'category' => 'community',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_post_file_count_and_external_link_limits_use_runtime_settings(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');
        $settings = app(SettingsService::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $settings->set('community.max_files', 1, ['type' => 'integer']);

        $this->post('/api/posts', [
            'title' => 'Runtime file count concept',
            'content' => 'This concept has enough description to satisfy validation.',
            'images' => [
                UploadedFile::fake()->image('one.png'),
            ],
            'attachments' => [
                UploadedFile::fake()->create('two.pdf', 10, 'application/pdf'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments']);

        $settings->set('community.max_files', 12, ['type' => 'integer']);
        $settings->set('community.max_external_links', 0, ['type' => 'integer']);

        $this->postJson('/api/posts', [
            'title' => 'Runtime external link concept',
            'content' => 'This post has enough content and also links to https://example.com.',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_sensitive_words_force_pending_only_when_enabled(): void
    {
        $settings = app(SettingsService::class);
        $settings->setMany([
            'community.submission_policy' => ['value' => 'all_auto_approve', 'type' => 'string'],
            'community.sensitive_words_enabled' => ['value' => true, 'type' => 'boolean'],
            'community.sensitive_words' => ['value' => ['blocked'], 'type' => 'json'],
        ]);

        $user = User::factory()->create();
        $postOwner = User::factory()->create();
        $approvedPost = Post::factory()->create([
            'user_id' => $postOwner->id,
            'status' => 'approved',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'title' => 'Runtime sensitive concept',
            'content' => 'This post contains the blocked phrase and should wait for review.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'This blocked comment should wait for review.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $settings->set('community.sensitive_words_enabled', false, ['type' => 'boolean']);

        $this->postJson('/api/posts', [
            'title' => 'Runtime non sensitive concept',
            'content' => 'This post contains the blocked phrase but the scanner is off.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_submission_policy_can_require_approval_for_posts_and_comments(): void
    {
        app(SettingsService::class)->set('community.submission_policy', 'all_require_approval', ['type' => 'string']);

        $user = User::factory()->create();
        $postOwner = User::factory()->create();
        $approvedPost = Post::factory()->create([
            'user_id' => $postOwner->id,
            'status' => 'approved',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'title' => 'Runtime approval concept',
            'content' => 'This post should wait for moderation approval.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'This comment should also wait for moderation approval.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_public_settings_exposes_community_config_without_sensitive_words(): void
    {
        app(SettingsService::class)->setMany([
            'community.allowed_extensions' => ['value' => ['jpg', 'pdf'], 'type' => 'json'],
            'community.sensitive_words' => ['value' => ['private-term'], 'type' => 'json'],
            'community.default_funding_support_button_text' => ['value' => 'Back this idea', 'type' => 'string'],
        ]);

        $this->getJson('/api/public-settings')
            ->assertOk()
            ->assertJsonPath('data.community.allowed_extensions.0', 'jpg')
            ->assertJsonPath('data.community.default_funding_support_button_text', 'Back this idea')
            ->assertJsonMissingPath('data.community.sensitive_words');
    }

    public function test_default_funding_button_text_is_returned_from_public_settings_and_post_resource(): void
    {
        app(SettingsService::class)->set('community.default_funding_support_button_text', 'Back this idea', ['type' => 'string']);

        $post = Post::factory()->create([
            'status' => 'approved',
            'funding_url' => 'https://example.com/fund',
        ]);

        $this->getJson('/api/public-settings')
            ->assertOk()
            ->assertJsonPath('data.community.default_funding_support_button_text', 'Back this idea');

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.support_button_text', 'Back this idea');
    }
}
