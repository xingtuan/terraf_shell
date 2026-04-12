<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
