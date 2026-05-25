<?php

namespace Tests\Feature\Api;

use App\Enums\CommunitySubmissionPolicy;
use App\Filament\Pages\CommunityModerationSettings;
use App\Filament\Pages\CommunitySettings;
use App\Models\Post;
use App\Models\User;
use App\Services\CommunityModerationPolicyService;
use App\Services\CommunitySettingsService;
use App\Services\SensitiveContentService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use ReflectionClass;
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
        $this->assertTrue($communitySettings->sensitiveWordsEnabled());
        $this->assertSame(['alpha', 'beta'], $communitySettings->sensitiveWords());
        $this->assertSame('Back this idea', $communitySettings->defaultFundingSupportButtonText());
    }

    public function test_community_settings_page_no_longer_manages_submission_policy(): void
    {
        $settingsPage = new class extends CommunitySettings
        {
            public function exposedSettingMap(): array
            {
                return $this->settingMap();
            }
        };

        $this->assertArrayNotHasKey('submission_policy', $settingsPage->exposedSettingMap());

        $settingsSource = file_get_contents(app_path('Filament/Pages/CommunitySettings.php'));
        $moderationSource = file_get_contents(app_path('Filament/Pages/CommunityModerationSettings.php'));

        $this->assertIsString($settingsSource);
        $this->assertIsString($moderationSource);
        $this->assertStringNotContainsString("Select::make('submission_policy')", $settingsSource);
        $this->assertStringContainsString("Select::make('submission_policy')", $moderationSource);
        $this->assertTrue((new ReflectionClass(CommunityModerationSettings::class))->hasMethod('save'));
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
        app(CommunityModerationPolicyService::class)->update(
            CommunitySubmissionPolicy::AllAutoApprove,
        );
        $settings->setMany([
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

        $this->postJson('/api/posts', [
            'title' => 'Runtime sensitive JSON concept',
            'content_json' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Nested blocked phrase'],
                        ],
                    ],
                ],
            ]),
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'This blocked comment should wait for review.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $safePostId = $this->postJson('/api/posts', [
            'title' => 'Runtime safe concept',
            'content' => 'This post starts clean and can be published immediately.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->json('data.id');

        $this->patchJson("/api/posts/{$safePostId}", [
            'content' => 'This edited post contains the blocked phrase and should return to review.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.published_at', null);

        $settings->set('community.sensitive_words_enabled', false, ['type' => 'boolean']);

        $this->postJson('/api/posts', [
            'title' => 'Runtime non sensitive concept',
            'content' => 'This post contains the blocked phrase but the scanner is off.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_moderators_bypass_sensitive_word_pending_status(): void
    {
        app(CommunityModerationPolicyService::class)->update(
            CommunitySubmissionPolicy::AllAutoApprove,
        );
        app(SettingsService::class)->setMany([
            'community.sensitive_words_enabled' => ['value' => true, 'type' => 'boolean'],
            'community.sensitive_words' => ['value' => ['blocked'], 'type' => 'json'],
        ]);

        $moderator = User::factory()->moderator()->create();
        $postOwner = User::factory()->create();
        $approvedPost = Post::factory()->create([
            'user_id' => $postOwner->id,
            'status' => 'approved',
        ]);
        Sanctum::actingAs($moderator);

        $this->postJson('/api/posts', [
            'title' => 'Moderator sensitive concept',
            'content' => 'This blocked phrase can be published by a moderator.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'This blocked comment can be published by a moderator.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_sensitive_words_support_multiple_formats_and_content_json_text_nodes(): void
    {
        $settings = app(SettingsService::class);
        $scanner = app(SensitiveContentService::class);
        $document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Nested Alpha term'],
                    ],
                ],
            ],
        ];

        $settings->set('community.sensitive_words_enabled', true, ['type' => 'boolean']);
        $settings->set('community.sensitive_words', '["alpha","베타","风险"]', ['type' => 'string']);
        $this->assertSame(['alpha'], $scanner->scan(['content_json' => $document])['matched_terms']);

        $settings->set('community.sensitive_words', "alpha\n베타,风险", ['type' => 'string']);
        $this->assertSame(['베타'], $scanner->scan(['content' => '<p>contains 베타</p>'])['matched_terms']);

        $settings->set('community.sensitive_words_enabled', false, ['type' => 'boolean']);
        $this->assertSame([], $scanner->scan(['content' => 'alpha 베타 风险'])['matched_terms']);
    }

    public function test_submission_policy_can_require_approval_for_posts_and_comments(): void
    {
        app(CommunityModerationPolicyService::class)->update(
            CommunitySubmissionPolicy::AllRequireApproval,
        );

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

    public function test_runtime_submission_policy_setting_no_longer_overrides_moderation_settings(): void
    {
        app(SettingsService::class)->set('community.submission_policy', 'all_auto_approve', ['type' => 'string']);
        app(CommunityModerationPolicyService::class)->update(
            CommunitySubmissionPolicy::AllRequireApproval,
        );

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'title' => 'Moderation table policy wins',
            'content' => 'This ordinary post follows the moderation settings table.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_configured_community_attachment_extensions_are_enforced_without_mime_blocking(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');
        app(SettingsService::class)->set('community.allowed_extensions', ['srt', 'zip', 'stl', 'obj', 'step', 'dwg'], ['type' => 'json']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach ([
            ['captions.srt', 'text/plain'],
            ['archive.zip', 'application/octet-stream'],
            ['mesh.stl', 'application/octet-stream'],
            ['model.obj', 'application/octet-stream'],
            ['part.step', 'application/octet-stream'],
            ['drawing.dwg', 'application/octet-stream'],
        ] as $index => [$name, $mime]) {
            $this->post('/api/posts', [
                'title' => 'Runtime attachment '.$index,
                'content' => 'This concept includes a runtime-configured attachment type.',
                'attachments' => [
                    UploadedFile::fake()->create($name, 1, $mime),
                ],
            ], ['Accept' => 'application/json'])
                ->assertCreated();

            $this->assertDatabaseHas('idea_media', [
                'original_name' => $name,
                'media_type' => 'document',
            ]);
        }

        app(SettingsService::class)->set('community.allowed_extensions', ['pdf'], ['type' => 'json']);

        $this->post('/api/posts', [
            'title' => 'Blocked runtime attachment',
            'content' => 'This concept should fail because the configured extensions exclude srt.',
            'attachments' => [
                UploadedFile::fake()->create('captions.srt', 1, 'text/plain'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);
    }

    public function test_community_cover_upload_still_requires_images(): void
    {
        Config::set('community.uploads.disk', 'public');
        Storage::fake('public');
        app(SettingsService::class)->set('community.allowed_extensions', ['jpg', 'srt', 'zip', 'stl'], ['type' => 'json']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['cover.srt', 'cover.zip', 'cover.stl'] as $name) {
            $this->post('/api/media/upload', [
                'file' => UploadedFile::fake()->create($name, 1, 'application/octet-stream'),
                'category' => 'community-cover',
            ], ['Accept' => 'application/json'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
        }
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
            ->assertJsonMissingPath('data.community.sensitive_words')
            ->assertJsonMissingPath('data.community.submission_policy');
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
