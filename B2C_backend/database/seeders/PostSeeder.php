<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Follow;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()
            ->where('role', UserRole::Creator->value)
            ->where('is_banned', false)
            ->get();
        $categories = Category::all();
        $tags = Tag::all();

        if ($users->isEmpty() || $categories->isEmpty() || $tags->isEmpty()) {
            return;
        }

        Post::factory()
            ->count(12)
            ->create([
                'status' => ContentStatus::Approved->value,
            ])
            ->each(function (Post $post) use ($users, $categories, $tags): void {
                $post->update([
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);

                $post->tags()->sync($tags->random(rand(2, 4))->pluck('id'));

                $comments = Comment::factory()
                    ->count(rand(2, 5))
                    ->create([
                        'post_id' => $post->id,
                        'status' => ContentStatus::Approved->value,
                        'user_id' => $users->random()->id,
                    ]);

                foreach ($comments->take(2) as $comment) {
                    Comment::factory()->create([
                        'post_id' => $post->id,
                        'parent_id' => $comment->id,
                        'status' => ContentStatus::Approved->value,
                        'user_id' => $users->random()->id,
                    ]);
                }

                $postLikeUsers = $users->random(rand(1, min(4, $users->count())));
                foreach ($postLikeUsers as $user) {
                    PostLike::query()->firstOrCreate([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                    ]);
                }

                $favoriteUsers = $users->random(rand(1, min(3, $users->count())));
                foreach ($favoriteUsers as $user) {
                    Favorite::query()->firstOrCreate([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                    ]);
                }

                $post->update([
                    'comments_count' => $post->comments()->where('status', ContentStatus::Approved->value)->count(),
                    'likes_count' => $post->likes()->count(),
                    'favorites_count' => $post->favorites()->count(),
                ]);
            });

        foreach ($users as $user) {
            $others = $users->where('id', '!=', $user->id)->values();
            foreach ($others->random(rand(1, min(3, $others->count()))) as $other) {
                Follow::query()->firstOrCreate([
                    'follower_id' => $user->id,
                    'following_id' => $other->id,
                ]);
            }
        }

        $reportedPost = Post::query()->inRandomOrder()->first();
        $reporter = $users->where('id', '!=', $reportedPost?->user_id)->first();

        if ($reportedPost !== null && $reporter !== null) {
            Report::query()->firstOrCreate([
                'reporter_id' => $reporter->id,
                'target_type' => 'post',
                'target_id' => $reportedPost->id,
            ], [
                'reason' => 'Off-topic or low-quality content',
                'description' => 'This post does not match the category and feels promotional.',
            ]);

            UserNotification::query()->create([
                'recipient_user_id' => $reportedPost->user_id,
                'actor_user_id' => $reporter->id,
                'type' => 'like',
                'target_type' => 'post',
                'target_id' => $reportedPost->id,
                'data' => [
                    'message' => 'Someone interacted with your post.',
                ],
            ]);
        }
    }
}
