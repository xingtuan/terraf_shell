<?php

namespace Tests\Feature\Api;

use App\Enums\CommunitySubmissionPolicy;
use App\Models\Post;
use App\Models\User;
use App\Services\CommunityModerationPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityModerationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_auto_approve_policy_publishes_posts_and_comments_immediately(): void
    {
        app(CommunityModerationPolicyService::class)->update(
            CommunitySubmissionPolicy::AllAutoApprove,
        );

        $user = User::factory()->create();
        $postOwner = User::factory()->create();
        $approvedPost = Post::factory()->create([
            'user_id' => $postOwner->id,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'title' => 'Auto-approved shell stool concept',
            'content' => 'This concept should go live immediately when the global policy allows it.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'This comment should also be visible immediately under auto-approval.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_trusted_users_policy_only_auto_approves_selected_users(): void
    {
        $trustedUser = User::factory()->create();
        $regularUser = User::factory()->create();
        $postOwner = User::factory()->create();
        $approvedPost = Post::factory()->create([
            'user_id' => $postOwner->id,
            'status' => 'approved',
        ]);

        app(CommunityModerationPolicyService::class)->update(
            CommunitySubmissionPolicy::TrustedUsersAutoApprove,
            [$trustedUser->id],
        );

        $this->assertDatabaseHas('users', [
            'id' => $trustedUser->id,
            'community_auto_approve' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $regularUser->id,
            'community_auto_approve' => false,
        ]);

        Sanctum::actingAs($trustedUser->fresh());
        $this->postJson('/api/posts', [
            'title' => 'Trusted creator concept',
            'content' => 'Trusted creators should bypass the moderation queue for new posts.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'Trusted users should bypass moderation for comments too.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        Sanctum::actingAs($regularUser->fresh());
        $this->postJson('/api/posts', [
            'title' => 'Regular creator concept',
            'content' => 'Regular creators should still enter the moderation queue for new posts.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'body' => 'Regular users should still enter the moderation queue for comments.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
    }
}
