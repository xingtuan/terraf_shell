<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_owner_can_edit_comment_and_it_returns_to_pending(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'comments_count' => 1,
            'status' => 'approved',
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'content' => 'Original comment',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/comments/{$comment->id}", [
            'content' => 'Updated comment text',
        ])->assertOk()
            ->assertJsonPath('data.content', 'Updated comment text')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.can_edit', true);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'pending',
            'content' => 'Updated comment text',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'comments_count' => 0,
        ]);
    }

    public function test_admin_can_edit_comment_without_requeueing_it(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'comments_count' => 1,
            'status' => 'approved',
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
            'status' => 'approved',
            'content' => 'Original comment',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/comments/{$comment->id}", [
            'content' => 'Admin edited comment',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'comments_count' => 1,
        ]);
    }

    public function test_user_cannot_edit_another_users_comment(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $this->patchJson("/api/comments/{$comment->id}", [
            'content' => 'Unauthorized update',
        ])->assertForbidden();
    }
}
