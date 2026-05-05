<?php

namespace Tests\Feature\Api;

use App\Support\StorageUrl;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_fetch_current_profile_and_verify_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'creator')
            ->assertJsonPath('data.user.account_status', 'active')
            ->assertJsonPath('data.user.email_verified', false);

        $this->assertNotEmpty($response->json('data.user.username'));

        $user = User::query()->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);

        $token = $response->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.email_verified', false);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('data.email_verified', true);
    }

    public function test_authenticated_user_can_update_expanded_profile_and_email_change_requires_reverification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this
            ->patchJson('/api/auth/profile', [
                'bio' => 'Builder, reviewer, and product enthusiast.',
                'school_or_company' => 'Auckland Design Lab',
                'region' => 'Auckland, New Zealand',
                'portfolio_url' => 'https://portfolio.example.com',
                'open_to_collab' => true,
                'email' => 'new-john@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.profile.bio', 'Builder, reviewer, and product enthusiast.')
            ->assertJsonPath('data.profile.school_or_company', 'Auckland Design Lab')
            ->assertJsonPath('data.profile.region', 'Auckland, New Zealand')
            ->assertJsonPath('data.profile.location', 'Auckland, New Zealand')
            ->assertJsonPath('data.profile.portfolio_url', 'https://portfolio.example.com')
            ->assertJsonPath('data.profile.website', 'https://portfolio.example.com')
            ->assertJsonPath('data.profile.open_to_collab', true)
            ->assertJsonPath('data.email', 'new-john@example.com')
            ->assertJsonPath('data.email_verified', false);

        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    public function test_authenticated_user_can_update_profile_with_preuploaded_avatar_path(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        config()->set('community.uploads.azure.use_sas_urls', false);
        config()->set('community.uploads.disk', 'azure');
        config()->set('filesystems.disks.azure.storage_url', 'https://example.blob.core.windows.net');
        config()->set('filesystems.disks.azure.container', 'uploads');

        $this
            ->patchJson('/api/auth/profile', [
                'name' => 'Updated User',
                'username' => 'updated_user',
                'bio' => 'Updated profile bio.',
                'avatar_path' => 'avatars/test-avatar.png',
                'avatar_url' => 'https://example.com/avatars/test-avatar.png',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated User')
            ->assertJsonPath('data.username', 'updated_user')
            ->assertJsonPath('data.profile.bio', 'Updated profile bio.')
            ->assertJsonPath('data.profile.avatar_url', StorageUrl::publicResolve('avatars/test-avatar.png', 'azure'));
    }

    public function test_registration_auto_generates_username_from_email_local_part(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();

        $user = User::query()->where('email', 'sara@example.com')->firstOrFail();
        $this->assertStringStartsWith('sara', $user->username);
    }

    public function test_registration_applies_collision_suffix_when_username_is_taken(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Sara A',
            'email' => 'sara@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $this->postJson('/api/auth/register', [
            'name' => 'Sara B',
            'email' => 'sara@other.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $first = User::query()->where('email', 'sara@example.com')->firstOrFail();
        $second = User::query()->where('email', 'sara@other.com')->firstOrFail();

        $this->assertNotEquals($first->username, $second->username);
        $this->assertStringStartsWith('sara_', $second->username);
    }

    public function test_registration_falls_back_to_user_prefix_for_short_or_special_char_email_local_part(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'A',
            'email' => 'a@x.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();

        $user = User::query()->where('email', 'a@x.com')->firstOrFail();
        $this->assertStringStartsWith('user', $user->username);
    }

    public function test_duplicate_registration_email_returns_field_level_error(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Taken Email',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'This email is already registered. Try signing in instead.');
    }

    public function test_user_can_update_username_via_profile_endpoint(): void
    {
        $user = User::factory()->create(['username' => 'original_handle']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', ['username' => 'new_handle'])
            ->assertOk()
            ->assertJsonPath('data.username', 'new_handle');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'new_handle']);
    }

    public function test_username_uniqueness_enforced_on_profile_update(): void
    {
        $taken = User::factory()->create(['username' => 'taken_handle']);
        $user = User::factory()->create(['username' => 'my_handle']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', ['username' => 'taken_handle'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    }

    public function test_user_can_request_password_reset_and_log_in_with_new_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset-me@example.com',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'newpassword123',
        ])->assertOk();
    }
}
