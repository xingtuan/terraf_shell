<?php

namespace Tests\Feature\Api;

use App\Enums\AccountStatus;
use App\Enums\ContentStatus;
use App\Enums\UserViolationStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GovernancePhaseSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_review_user_moderation_history_violations_and_admin_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $creator = User::factory()->create();
        $reporter = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $creator->id,
            'status' => ContentStatus::Approved->value,
        ]);

        $comment = Comment::factory()->pending()->create([
            'post_id' => $post->id,
            'user_id' => $creator->id,
        ]);

        Sanctum::actingAs($reporter);
        $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Misleading material claims',
            'description' => 'This concept needs moderation review.',
        ])->assertCreated();

        Sanctum::actingAs($moderator);
        $this->patchJson("/api/admin/posts/{$post->id}/status", [
            'status' => ContentStatus::Hidden->value,
            'reason' => 'Temporarily taken down during review.',
        ])->assertOk();

        $this->patchJson("/api/admin/comments/{$comment->id}/status", [
            'status' => ContentStatus::Rejected->value,
            'reason' => 'Comment violates the posting rules.',
        ])->assertOk();

        $manualViolationResponse = $this->postJson("/api/admin/users/{$creator->id}/violations", [
            'type' => 'manual_warning',
            'severity' => 'warning',
            'reason' => 'Formal warning issued by moderation.',
            'subject_type' => 'post',
            'subject_id' => $post->id,
        ])->assertCreated();

        $manualViolationId = $manualViolationResponse->json('data.id');

        $this->patchJson("/api/admin/users/{$creator->id}/violations/{$manualViolationId}", [
            'status' => UserViolationStatus::Resolved->value,
            'resolution_note' => 'Creator acknowledged the warning.',
        ])->assertOk();

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/users/{$creator->id}/account-status", [
            'account_status' => AccountStatus::Restricted->value,
            'reason' => 'Escalated account review.',
        ])->assertOk();

        Sanctum::actingAs($moderator);
        $this->getJson("/api/admin/users/{$creator->id}/moderation-history")
            ->assertOk()
            ->assertJsonFragment(['action' => 'report.submitted'])
            ->assertJsonFragment(['action' => 'post.status_updated'])
            ->assertJsonFragment(['action' => 'comment.status_updated'])
            ->assertJsonFragment(['action' => 'user.account_status_updated'])
            ->assertJsonFragment(['action' => 'user.violation_status_updated']);

        $this->getJson("/api/admin/users/{$creator->id}/violations")
            ->assertOk()
            ->assertJsonFragment(['type' => 'content_hidden'])
            ->assertJsonFragment(['type' => 'content_rejected'])
            ->assertJsonFragment(['type' => 'account_restricted']);

        $this->getJson("/api/admin/users/{$creator->id}/violations?status=resolved")
            ->assertOk()
            ->assertJsonFragment(['type' => 'manual_warning'])
            ->assertJsonFragment(['status' => UserViolationStatus::Resolved->value]);

        $this->getJson("/api/admin/users/{$creator->id}/admin-actions")
            ->assertOk()
            ->assertJsonFragment(['action' => 'user.account_status_updated'])
            ->assertJsonFragment(['action' => 'user.violation_recorded'])
            ->assertJsonFragment(['action' => 'user.violation_status_updated']);

        $this->assertDatabaseHas('moderation_logs', [
            'target_user_id' => $creator->id,
            'action' => 'report.submitted',
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'target_user_id' => $creator->id,
            'action' => 'user.account_status_updated',
        ]);
    }

    public function test_review_history_endpoints_and_violation_resolution_are_traceable(): void
    {
        $moderator = User::factory()->moderator()->create();
        $creator = User::factory()->create();

        $post = Post::factory()->pending()->create([
            'user_id' => $creator->id,
        ]);

        Sanctum::actingAs($moderator);
        $this->patchJson("/api/admin/posts/{$post->id}/status", [
            'status' => ContentStatus::Approved->value,
            'reason' => 'Initial approval.',
        ])->assertOk();

        $this->patchJson("/api/admin/posts/{$post->id}/status", [
            'status' => ContentStatus::Hidden->value,
            'reason' => 'Temporarily hidden.',
        ])->assertOk();

        $this->patchJson("/api/admin/posts/{$post->id}/status", [
            'status' => ContentStatus::Approved->value,
            'reason' => 'Restored after review.',
        ])->assertOk();

        $comment = Comment::factory()->pending()->create([
            'post_id' => $post->id,
            'user_id' => $creator->id,
        ]);

        $this->patchJson("/api/admin/comments/{$comment->id}/status", [
            'status' => ContentStatus::Hidden->value,
            'reason' => 'Comment hidden.',
        ])->assertOk();

        $this->patchJson("/api/admin/comments/{$comment->id}/status", [
            'status' => ContentStatus::Approved->value,
            'reason' => 'Comment restored.',
        ])->assertOk();

        $this->getJson("/api/admin/posts/{$post->id}/review-history")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['action' => 'post.status_updated']);

        $this->getJson("/api/admin/comments/{$comment->id}/review-history")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['action' => 'comment.status_updated']);

        $this->getJson("/api/admin/users/{$creator->id}/violations?status=resolved")
            ->assertOk()
            ->assertJsonFragment(['type' => 'content_hidden'])
            ->assertJsonFragment(['status' => UserViolationStatus::Resolved->value]);
    }

    public function test_sensitive_word_framework_records_violations_and_logs(): void
    {
        config()->set('community.moderation.sensitive_words.enabled', true);
        config()->set('community.moderation.sensitive_words.terms', ['scamword']);

        $creator = User::factory()->create();
        $owner = User::factory()->create();
        $approvedPost = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => ContentStatus::Approved->value,
        ]);

        Sanctum::actingAs($creator);

        $postResponse = $this->postJson('/api/posts', [
            'title' => 'Scamword chair concept',
            'content' => 'This concept includes scamword in the draft copy.',
        ])->assertCreated();

        $postId = $postResponse->json('data.id');

        $commentResponse = $this->postJson("/api/posts/{$approvedPost->id}/comments", [
            'content' => 'This comment also includes scamword language.',
        ])->assertCreated();

        $commentId = $commentResponse->json('data.id');

        $reportResponse = $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $approvedPost->id,
            'reason' => 'scamword abuse report',
            'description' => 'The description repeats scamword.',
        ])->assertCreated();

        $reportId = $reportResponse->json('data.id');

        $this->assertDatabaseHas('user_violations', [
            'user_id' => $creator->id,
            'type' => 'sensitive_word',
            'subject_type' => 'post',
            'subject_id' => $postId,
        ]);

        $this->assertDatabaseHas('user_violations', [
            'user_id' => $creator->id,
            'type' => 'sensitive_word',
            'subject_type' => 'comment',
            'subject_id' => $commentId,
        ]);

        $this->assertDatabaseHas('user_violations', [
            'user_id' => $creator->id,
            'type' => 'sensitive_word',
            'subject_type' => 'report',
            'subject_id' => $reportId,
        ]);

        $this->assertDatabaseHas('moderation_logs', [
            'target_user_id' => $creator->id,
            'action' => 'post.sensitive_word_flagged',
        ]);

        $this->assertDatabaseHas('moderation_logs', [
            'target_user_id' => $creator->id,
            'action' => 'comment.sensitive_word_flagged',
        ]);

        $this->assertDatabaseHas('moderation_logs', [
            'target_user_id' => $creator->id,
            'action' => 'report.sensitive_word_flagged',
        ]);
    }

    public function test_restricted_users_are_blocked_from_participation_endpoints(): void
    {
        $restrictedUser = User::factory()->restricted()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => ContentStatus::Approved->value,
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
            'status' => ContentStatus::Approved->value,
        ]);

        Sanctum::actingAs($restrictedUser);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Restricted users should not be able to comment.',
        ])->assertForbidden();

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertForbidden();

        $this->postJson("/api/comments/{$comment->id}/like")
            ->assertForbidden();

        $this->postJson("/api/users/{$owner->id}/follow")
            ->assertForbidden();

        $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Restricted users should not be able to report.',
        ])->assertForbidden();
    }
}
