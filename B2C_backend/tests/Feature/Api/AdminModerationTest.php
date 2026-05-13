<?php

namespace Tests\Feature\Api;

use App\Enums\AccountStatus;
use App\Models\Post;
use App\Models\User;
use App\Services\AdminModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_reports_and_ban_users(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($reporter);
        $reportResponse = $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Spam or misleading information',
            'description' => 'This looks promotional and off-topic.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.target_id', $post->id);

        $reportId = $reportResponse->json('data.id');

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/reports')
            ->assertOk()
            ->assertJsonPath('data.0.id', $reportId);

        $this->patchJson("/api/admin/reports/{$reportId}/status", [
            'status' => 'resolved',
            'reason' => 'Reviewed and handled by moderation.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->patchJson("/api/admin/users/{$owner->id}/ban", [
            'is_banned' => true,
            'reason' => 'Repeated abusive behavior.',
        ])
            ->assertOk()
            ->assertJsonPath('data.is_banned', true);

        Sanctum::actingAs($owner->fresh());
        $this->postJson('/api/posts', [
            'title' => 'This should be blocked',
            'content' => 'Banned users should not be able to post.',
        ])
            ->assertForbidden();
    }

    public function test_only_staff_can_access_moderation_endpoints_and_only_admin_can_manage_roles_and_statuses(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $creator = User::factory()->create();
        $post = Post::factory()->pending()->create([
            'user_id' => $creator->id,
        ]);

        Sanctum::actingAs($moderator);
        $this->getJson('/api/admin/reports')
            ->assertOk();

        $this->patchJson("/api/admin/posts/{$post->id}/status", [
            'status' => 'approved',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->patchJson("/api/admin/users/{$creator->id}/role", [
            'role' => 'sme_partner',
        ])->assertForbidden();

        Sanctum::actingAs($creator);
        $this->getJson('/api/admin/reports')
            ->assertForbidden();

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/users/{$creator->id}/role", [
            'role' => 'visitor',
        ])
            ->assertOk()
            ->assertJsonPath('data.role', 'visitor');

        $this->patchJson("/api/admin/users/{$creator->id}/account-status", [
            'account_status' => 'restricted',
            'reason' => 'Under manual review.',
        ])
            ->assertOk()
            ->assertJsonPath('data.account_status', 'restricted')
            ->assertJsonPath('data.is_restricted', true);

        Sanctum::actingAs($creator->fresh());
        $this->postJson('/api/posts', [
            'title' => 'Restricted creators should be blocked',
            'content' => 'This submission should not be accepted.',
        ])->assertForbidden();
    }

    public function test_admin_can_ban_unban_and_user_can_login_again(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->create([
            'email' => 'restore-me@example.com',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$creator->id}/ban", [
            'is_banned' => true,
            'reason' => 'Repeated abuse.',
        ])
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Banned->value)
            ->assertJsonPath('data.is_banned', true);

        $this->postJson('/api/auth/login', [
            'email' => 'restore-me@example.com',
            'password' => 'password',
        ])->assertForbidden();

        $this->patchJson("/api/admin/users/{$creator->id}/account-status", [
            'account_status' => AccountStatus::Active->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Active->value)
            ->assertJsonPath('data.is_banned', false)
            ->assertJsonPath('data.is_restricted', false);

        $creator->refresh();

        $this->assertFalse($creator->is_banned);
        $this->assertNull($creator->banned_at);
        $this->assertNull($creator->ban_reason);
        $this->assertNull($creator->restricted_at);
        $this->assertNull($creator->restriction_reason);

        $this->postJson('/api/auth/login', [
            'email' => 'restore-me@example.com',
            'password' => 'password',
        ])->assertOk();
    }

    public function test_setting_account_status_active_clears_legacy_ban_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->create([
            'account_status' => AccountStatus::Active->value,
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => 'Legacy ban flag.',
            'restricted_at' => now(),
            'restriction_reason' => 'Legacy restriction.',
        ]);

        app(AdminModerationService::class)->updateAccountStatus(
            $creator,
            AccountStatus::Active->value,
            $admin,
            'Restored after review.'
        );

        $creator->refresh();

        $this->assertSame(AccountStatus::Active->value, $creator->account_status);
        $this->assertFalse($creator->is_banned);
        $this->assertFalse($creator->isBanned());
        $this->assertNull($creator->banned_at);
        $this->assertNull($creator->ban_reason);
        $this->assertNull($creator->restricted_at);
        $this->assertNull($creator->restriction_reason);
    }

    public function test_ban_endpoint_supports_unban_without_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->banned()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$creator->id}/ban", [
            'is_banned' => false,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'User unbanned successfully.')
            ->assertJsonPath('data.account_status', AccountStatus::Active->value)
            ->assertJsonPath('data.is_banned', false);

        $creator->refresh();

        $this->assertSame(AccountStatus::Active->value, $creator->account_status);
        $this->assertFalse($creator->is_banned);
        $this->assertNull($creator->banned_at);
        $this->assertNull($creator->ban_reason);
    }

    public function test_private_user_response_includes_participation_restriction_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->restricted()->create([
            'restriction_reason' => 'Cooling-off period.',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/users/{$creator->id}")
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Restricted->value)
            ->assertJsonPath('data.is_restricted', true)
            ->assertJsonPath('data.participation_restriction_reason', 'Cooling-off period.');
    }

    public function test_current_user_responses_include_participation_restriction_reason(): void
    {
        $creator = User::factory()->restricted()->create([
            'restriction_reason' => 'Cooling-off period.',
        ]);

        Sanctum::actingAs($creator);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Restricted->value)
            ->assertJsonPath('data.is_restricted', true)
            ->assertJsonPath('data.participation_restriction_reason', 'Cooling-off period.');

        $this->getJson("/api/users/{$creator->username}")
            ->assertOk()
            ->assertJsonPath('data.account_status', AccountStatus::Restricted->value)
            ->assertJsonPath('data.is_restricted', true)
            ->assertJsonPath('data.participation_restriction_reason', 'Cooling-off period.');
    }

    public function test_user_form_does_not_expose_editable_account_status_select(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/Users/Schemas/UserForm.php'));

        $this->assertStringNotContainsString("Select::make('account_status')", $source);
        $this->assertStringContainsString("Placeholder::make('account_status')", $source);
    }

    public function test_repair_account_status_command_dry_runs_and_fixes_inconsistent_users(): void
    {
        $admin = User::factory()->admin()->create();
        $legacyBan = User::factory()->create([
            'account_status' => AccountStatus::Active->value,
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => 'Legacy ban.',
        ]);
        $restored = User::factory()->create([
            'account_status' => AccountStatus::Active->value,
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => 'Already restored.',
        ]);

        DB::table('admin_action_logs')->insert([
            'actor_user_id' => $admin->id,
            'target_user_id' => $restored->id,
            'action' => 'user.account_status_updated',
            'description' => 'Restored by admin.',
            'metadata' => json_encode(['from' => AccountStatus::Banned->value, 'to' => AccountStatus::Active->value]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('users:repair-account-status', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertTrue($legacyBan->fresh()->is_banned);
        $this->assertTrue($restored->fresh()->is_banned);

        $this->artisan('users:repair-account-status')
            ->assertExitCode(0);

        $this->assertSame(AccountStatus::Banned->value, $legacyBan->fresh()->account_status);
        $this->assertTrue($legacyBan->fresh()->is_banned);
        $this->assertSame(AccountStatus::Active->value, $restored->fresh()->account_status);
        $this->assertFalse($restored->fresh()->is_banned);
        $this->assertNull($restored->fresh()->banned_at);
        $this->assertNull($restored->fresh()->ban_reason);
    }

    public function test_creator_can_submit_concepts_but_visitor_and_sme_partner_cannot(): void
    {
        $creator = User::factory()->create();
        $visitor = User::factory()->visitor()->create();
        $smePartner = User::factory()->smePartner()->create();

        Sanctum::actingAs($creator);
        $this->postJson('/api/posts', [
            'title' => 'Creator concept',
            'content' => 'A new concept from a creator account.',
        ])->assertCreated();

        Sanctum::actingAs($visitor);
        $this->postJson('/api/posts', [
            'title' => 'Visitor concept',
            'content' => 'Visitors cannot submit concepts.',
        ])->assertForbidden();

        Sanctum::actingAs($smePartner);
        $this->postJson('/api/posts', [
            'title' => 'Partner concept',
            'content' => 'SME partners cannot submit concepts yet.',
        ])->assertForbidden();
    }
}
