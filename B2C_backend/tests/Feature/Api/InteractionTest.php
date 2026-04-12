<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_liking_a_post_creates_a_notification_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($actor);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $owner->id,
            'actor_user_id' => $actor->id,
            'type' => 'like',
            'target_type' => 'post',
            'target_id' => $post->id,
        ]);
    }

    public function test_user_can_read_notifications_after_being_followed(): void
    {
        $target = User::factory()->create();
        $actor = User::factory()->create();

        Sanctum::actingAs($actor);
        $this->postJson("/api/users/{$target->id}/follow")
            ->assertOk()
            ->assertJsonPath('data.is_following', true);

        Sanctum::actingAs($target);
        $notificationsResponse = $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'follow');

        $notificationId = $notificationsResponse->json('data.0.id');

        $this->patchJson("/api/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);
    }
}
