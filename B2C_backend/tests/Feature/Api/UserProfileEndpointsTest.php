<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_profile_includes_public_counts_and_viewer_follow_state(): void
    {
        $target = User::factory()->create();
        $viewer = User::factory()->create();
        $otherFollower = User::factory()->create();
        $followedUser = User::factory()->create();

        Post::factory()->count(2)->create([
            'user_id' => $target->id,
            'status' => 'approved',
        ]);
        Post::factory()->pending()->create([
            'user_id' => $target->id,
        ]);

        Comment::factory()->count(2)->create([
            'user_id' => $target->id,
            'status' => 'approved',
        ]);
        Comment::factory()->pending()->create([
            'user_id' => $target->id,
        ]);

        Follow::factory()->create([
            'follower_id' => $viewer->id,
            'following_id' => $target->id,
        ]);
        Follow::factory()->create([
            'follower_id' => $otherFollower->id,
            'following_id' => $target->id,
        ]);
        Follow::factory()->create([
            'follower_id' => $target->id,
            'following_id' => $followedUser->id,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.followers_count', 2)
            ->assertJsonPath('data.following_count', 1)
            ->assertJsonPath('data.posts_count', 2)
            ->assertJsonPath('data.comments_count', 2)
            ->assertJsonPath('data.is_following', true);
    }

    public function test_public_user_posts_endpoint_only_returns_approved_posts(): void
    {
        $user = User::factory()->create();

        $visiblePost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Visible user post',
            'status' => 'approved',
        ]);

        Post::factory()->pending()->create([
            'user_id' => $user->id,
            'title' => 'Pending user post',
        ]);

        $this->getJson("/api/users/{$user->id}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visiblePost->id)
            ->assertJsonPath('data.0.title', 'Visible user post');
    }

    public function test_public_user_favorites_endpoint_returns_favorited_posts(): void
    {
        $user = User::factory()->create();
        $favoritedPost = Post::factory()->create([
            'title' => 'Favorited post',
            'status' => 'approved',
        ]);
        $otherPost = Post::factory()->create([
            'title' => 'Other post',
            'status' => 'approved',
        ]);

        Favorite::query()->create([
            'user_id' => $user->id,
            'post_id' => $favoritedPost->id,
        ]);

        $favoritedPost->increment('favorites_count');

        $response = $this->getJson("/api/users/{$user->id}/favorites")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $favoritedPost->id)
            ->assertJsonPath('data.0.title', 'Favorited post');

        $this->assertSame($otherPost->id, Post::query()->findOrFail($otherPost->id)->id);
        $response->assertJsonMissingPath('data.1');
    }

    public function test_user_comments_endpoint_respects_public_and_owner_visibility(): void
    {
        $user = User::factory()->create();

        $approvedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'content' => 'Approved comment',
        ]);

        $pendingComment = Comment::factory()->pending()->create([
            'user_id' => $user->id,
            'content' => 'Pending comment',
        ]);

        $this->getJson("/api/users/{$user->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approvedComment->id);

        Sanctum::actingAs($user);

        $this->getJson("/api/users/{$user->id}/comments")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/users/{$user->id}/comments?status=pending")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingComment->id);
    }

    public function test_followers_and_following_endpoints_return_paginated_users(): void
    {
        $target = User::factory()->create();
        $viewer = User::factory()->create();
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        Follow::factory()->create([
            'follower_id' => $viewer->id,
            'following_id' => $target->id,
        ]);
        Follow::factory()->create([
            'follower_id' => $follower->id,
            'following_id' => $target->id,
        ]);
        Follow::factory()->create([
            'follower_id' => $target->id,
            'following_id' => $followed->id,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/users/{$target->id}/followers")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.is_following', false);

        $this->getJson("/api/users/{$target->id}/following")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $followed->id);
    }
}
