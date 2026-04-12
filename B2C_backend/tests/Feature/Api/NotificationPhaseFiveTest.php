<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_notifications_by_read_state_and_mark_them_as_read(): void
    {
        $recipient = User::factory()->create();

        $readNotification = UserNotification::factory()->read()->create([
            'recipient_user_id' => $recipient->id,
            'actor_user_id' => null,
            'type' => NotificationType::SystemAnnouncement->value,
            'target_type' => null,
            'target_id' => null,
            'title' => 'Read announcement',
            'body' => 'Already read.',
            'action_url' => '/updates/read',
            'data' => [
                'title' => 'Read announcement',
                'body' => 'Already read.',
                'message' => 'Already read.',
                'action_url' => '/updates/read',
            ],
        ]);

        $unreadNotification = UserNotification::factory()->create([
            'recipient_user_id' => $recipient->id,
            'actor_user_id' => null,
            'type' => NotificationType::SystemAnnouncement->value,
            'target_type' => null,
            'target_id' => null,
            'title' => 'Unread announcement',
            'body' => 'Needs attention.',
            'action_url' => '/updates/unread',
            'data' => [
                'title' => 'Unread announcement',
                'body' => 'Needs attention.',
                'message' => 'Needs attention.',
                'action_url' => '/updates/unread',
            ],
        ]);

        Sanctum::actingAs($recipient);

        $this->getJson('/api/notifications?read=unread')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unreadNotification->id)
            ->assertJsonPath('data.0.title', 'Unread announcement')
            ->assertJsonPath('data.0.body', 'Needs attention.')
            ->assertJsonPath('data.0.action_url', '/updates/unread')
            ->assertJsonPath('meta.unread_count', 1);

        $this->patchJson("/api/notifications/{$unreadNotification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->getJson('/api/notifications?read=read')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.unread_count', 0);

        $this->assertDatabaseHas('notifications', [
            'id' => $readNotification->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $unreadNotification->id,
            'is_read' => true,
        ]);
    }

    public function test_like_and_favorite_notifications_return_consistent_payloads(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => ContentStatus::Approved->value,
        ]);

        Sanctum::actingAs($actor);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk();

        $this->postJson("/api/posts/{$post->id}/favorite")
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $owner->id,
            'actor_user_id' => $actor->id,
            'type' => NotificationType::Like->value,
            'target_type' => 'post',
            'target_id' => $post->id,
            'title' => 'New like on your concept',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $owner->id,
            'actor_user_id' => $actor->id,
            'type' => NotificationType::Favorite->value,
            'target_type' => 'post',
            'target_id' => $post->id,
            'title' => 'Your concept was favorited',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/notifications?type=like')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', NotificationType::Like->value)
            ->assertJsonPath('data.0.target.id', $post->id)
            ->assertJsonPath('data.0.action_url', '/posts/'.$post->slug);

        $this->getJson('/api/notifications?type=favorite')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', NotificationType::Favorite->value)
            ->assertJsonPath('data.0.title', 'Your concept was favorited')
            ->assertJsonPath('data.0.data.post_id', $post->id);
    }

    public function test_post_approval_rejection_and_featuring_create_notifications(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $approvedPost = Post::factory()->pending()->create([
            'user_id' => $creator->id,
            'title' => 'Feature Candidate',
        ]);

        $rejectedPost = Post::factory()->pending()->create([
            'user_id' => $creator->id,
            'title' => 'Rejected Candidate',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/posts/{$approvedPost->id}/status", [
            'status' => ContentStatus::Approved->value,
            'reason' => 'Ready for public discovery.',
        ])->assertOk();

        $this->patchJson("/api/admin/posts/{$approvedPost->id}/feature", [
            'is_featured' => true,
            'reason' => 'Homepage spotlight.',
        ])->assertOk();

        $this->patchJson("/api/admin/posts/{$rejectedPost->id}/status", [
            'status' => ContentStatus::Rejected->value,
            'reason' => 'Needs stronger technical detail.',
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $creator->id,
            'actor_user_id' => $admin->id,
            'type' => NotificationType::SubmissionApproved->value,
            'target_type' => 'post',
            'target_id' => $approvedPost->id,
            'title' => 'Concept approved',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $creator->id,
            'actor_user_id' => $admin->id,
            'type' => NotificationType::ConceptFeatured->value,
            'target_type' => 'post',
            'target_id' => $approvedPost->id,
            'title' => 'Concept featured',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $creator->id,
            'actor_user_id' => $admin->id,
            'type' => NotificationType::SubmissionRejected->value,
            'target_type' => 'post',
            'target_id' => $rejectedPost->id,
            'title' => 'Concept rejected',
        ]);

        Sanctum::actingAs($creator);

        $this->getJson('/api/notifications?type=submission_approved')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.target.id', $approvedPost->id);

        $this->getJson('/api/notifications?type=concept_featured')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Concept featured');

        $this->getJson('/api/notifications?type=submission_rejected')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.reason', 'Needs stronger technical detail.');
    }

    public function test_comment_and_reply_notifications_are_sent_when_content_is_approved(): void
    {
        $postOwner = User::factory()->create();
        $commentAuthor = User::factory()->create();
        $replyAuthor = User::factory()->create();
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'status' => ContentStatus::Approved->value,
        ]);

        Sanctum::actingAs($commentAuthor);
        $commentResponse = $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Pending review comment.',
        ])->assertCreated();

        $commentId = $commentResponse->json('data.id');

        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $postOwner->id,
            'type' => NotificationType::Comment->value,
            'target_id' => $commentId,
        ]);

        Sanctum::actingAs($moderator);
        $this->patchJson("/api/admin/comments/{$commentId}/status", [
            'status' => ContentStatus::Approved->value,
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $postOwner->id,
            'actor_user_id' => $moderator->id,
            'type' => NotificationType::Comment->value,
            'target_type' => 'comment',
            'target_id' => $commentId,
        ]);

        Sanctum::actingAs($replyAuthor);
        $replyResponse = $this->postJson("/api/comments/{$commentId}/reply", [
            'content' => 'Pending review reply.',
        ])->assertCreated();

        $replyId = $replyResponse->json('data.id');

        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $commentAuthor->id,
            'type' => NotificationType::Reply->value,
            'target_id' => $replyId,
        ]);

        Sanctum::actingAs($moderator);
        $this->patchJson("/api/admin/comments/{$replyId}/status", [
            'status' => ContentStatus::Approved->value,
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $commentAuthor->id,
            'actor_user_id' => $moderator->id,
            'type' => NotificationType::Reply->value,
            'target_type' => 'comment',
            'target_id' => $replyId,
        ]);
    }

    public function test_admin_can_broadcast_system_announcements_to_target_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->create(['role' => UserRole::Creator->value]);
        $partner = User::factory()->smePartner()->create();
        $moderator = User::factory()->moderator()->create();
        $restrictedCreator = User::factory()->restricted()->create([
            'role' => UserRole::Creator->value,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/notifications/announcements', [
            'title' => 'Platform update',
            'body' => 'Material showcase content has been refreshed.',
            'action_url' => '/materials/premium-oyster-shell',
            'roles' => [UserRole::Creator->value, UserRole::SmePartner->value],
        ])->assertCreated()
            ->assertJsonPath('data.sent_count', 2);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $creator->id,
            'type' => NotificationType::SystemAnnouncement->value,
            'title' => 'Platform update',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $partner->id,
            'type' => NotificationType::SystemAnnouncement->value,
            'title' => 'Platform update',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $moderator->id,
            'type' => NotificationType::SystemAnnouncement->value,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $restrictedCreator->id,
            'type' => NotificationType::SystemAnnouncement->value,
        ]);

        Sanctum::actingAs($creator);

        $this->getJson('/api/notifications?type=system_announcement')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Platform update')
            ->assertJsonPath('data.0.body', 'Material showcase content has been refreshed.')
            ->assertJsonPath('data.0.action_url', '/materials/premium-oyster-shell');
    }
}
