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

    public function test_user_can_reply_to_a_reply_and_receive_nested_comment_tree(): void
    {
        $author = User::factory()->create();
        $replier = User::factory()->create();
        $nestedReplier = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'approved',
        ]);

        $rootComment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'status' => 'approved',
            'content' => 'Root comment',
        ]);

        $firstReply = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $replier->id,
            'parent_id' => $rootComment->id,
            'status' => 'approved',
            'content' => 'First reply',
        ]);

        Sanctum::actingAs($nestedReplier);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'Nested reply',
            'parent_id' => $firstReply->id,
        ])->assertCreated()
            ->assertJsonPath('data.parent_id', $firstReply->id)
            ->assertJsonPath('data.content', 'Nested reply');

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.id', $rootComment->id)
            ->assertJsonPath('data.0.replies.0.id', $firstReply->id)
            ->assertJsonPath('data.0.replies.0.replies.0.parent_id', $firstReply->id)
            ->assertJsonPath('data.0.replies.0.replies.0.content', 'Nested reply');
    }
}
