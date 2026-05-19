<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\FundingCampaignStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Follow;
use App\Models\FundingCampaign;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        foreach ($this->demoPosts() as $index => $demoPost) {
            $post = Post::query()->updateOrCreate(
                ['slug' => $demoPost['slug']],
                [
                    'status' => ContentStatus::Approved->value,
                    'user_id' => $users[$index % $users->count()]->id,
                    'category_id' => $categories[$index % $categories->count()]->id,
                    'title' => $demoPost['title'],
                    'content' => $demoPost['content'],
                    'content_json' => null,
                    'excerpt' => Str::limit(strip_tags($demoPost['content']), 180),
                    'cover_image_url' => null,
                    'cover_image_path' => null,
                    'reading_time' => 1,
                    'is_pinned' => $index === 0,
                    'is_featured' => $index < 3,
                    'is_demo_content' => true,
                    'engagement_score' => 0,
                    'trending_score' => 0,
                    'featured_at' => $index < 3 ? now() : null,
                    'featured_by' => null,
                    'published_at' => now()->subDays($index + 1),
                ]
            );

            $post->tags()->sync($this->tagIdsForPost($tags, $index));
            $this->seedComments($post, $users, $index);

            $postLikeUsers = $users->take(min(4, $users->count()));
            foreach ($postLikeUsers as $user) {
                PostLike::query()->firstOrCreate([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
            }

            $favoriteUsers = $users->skip(1)->take(min(3, max(1, $users->count() - 1)));
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
                'views_count' => 80 + ($index * 13),
            ]);
        }

        foreach ($users as $user) {
            $others = $users->where('id', '!=', $user->id)->values();
            foreach ($others->take(min(3, $others->count())) as $other) {
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

            UserNotification::query()->firstOrCreate([
                'recipient_user_id' => $reportedPost->user_id,
                'actor_user_id' => $reporter->id,
                'type' => 'like',
                'target_type' => 'post',
                'target_id' => $reportedPost->id,
            ], [
                'data' => [
                    'message' => 'Someone interacted with your post.',
                ],
            ]);
        }

        $supportPost = Post::query()
            ->approved()
            ->orderBy('id')
            ->first();

        if ($supportPost !== null) {
            FundingCampaign::query()->firstOrCreate([
                'post_id' => $supportPost->id,
            ], [
                'support_enabled' => true,
                'support_button_text' => 'Support this concept',
                'external_crowdfunding_url' => 'https://crowdfund.example.com/projects/premium-oyster-shell-concept',
                'campaign_status' => FundingCampaignStatus::Live->value,
                'target_amount' => 15000,
                'pledged_amount' => 4200,
                'backer_count' => 64,
                'reward_description' => 'Backers receive sample material tiles and early design updates.',
                'campaign_start_at' => now()->subDays(5),
                'campaign_end_at' => now()->addDays(25),
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, slug: string, content: string}>
     */
    private function demoPosts(): array
    {
        return [
            $this->demoPost('Oyster shell composite desk tray', 'A desk tray concept using OXP sheet offcuts for a refined office accessory. The prototype focuses on small-batch tooling, easy finishing, and repairable edges.'),
            $this->demoPost('Cafe service tile pilot', 'A hospitality tile concept for table numbers, tasting flights, and counter displays. The idea tests stain resistance, weight, and a quiet natural finish.'),
            $this->demoPost('Modular retail display blocks', 'Stackable display blocks for small retailers that need durable visual merchandising without disposable acrylic props.'),
            $this->demoPost('Community workshop sample kit', 'A compact sample kit designed for schools and makerspaces to compare finishes, thicknesses, and common joining methods.'),
            $this->demoPost('Acoustic wall accent study', 'An early study exploring textured wall accents for reception spaces, with attention to mounting, cleaning, and replacement.'),
            $this->demoPost('Restaurant menu stand concept', 'A simple menu stand made for repeated cleaning and heavy service use, designed around stable weight and understated material character.'),
            $this->demoPost('Circular material classroom prompt', 'A classroom activity prompt that helps students map waste streams, constraints, and useful product ideas before prototyping.'),
            $this->demoPost('Low-waste fixture bracket', 'A small fixture bracket concept that uses predictable nesting and simple drilling to reduce fabrication waste.'),
            $this->demoPost('Giftware packaging insert trial', 'A packaging insert trial comparing recycled paper structures with rigid reusable OXP inserts for premium product presentation.'),
            $this->demoPost('Public library signage marker', 'A tactile signage marker concept for public libraries that balances legibility, durability, and a calm material palette.'),
            $this->demoPost('Sample request display board', 'A display board concept for sales teams to explain colour, finish, weight, and care instructions during sample conversations.'),
            $this->demoPost('Repairable coaster set', 'A coaster set prototype designed to test edge sealing, day-to-day cleaning, and easy replacement of individual pieces.'),
        ];
    }

    /**
     * @return array{title: string, slug: string, content: string}
     */
    private function demoPost(string $title, string $content): array
    {
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $content,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function tagIdsForPost($tags, int $index): array
    {
        return $tags
            ->values()
            ->slice($index % max(1, $tags->count()), 3)
            ->whenEmpty(fn () => $tags->values()->take(3))
            ->pluck('id')
            ->all();
    }

    private function seedComments(Post $post, $users, int $index): void
    {
        $commentTexts = [
            'This direction looks practical for small-batch testing.',
            'The material story would be useful to show beside the prototype.',
            'I would like to see cleaning and edge-wear results next.',
            'This could work well as a pilot with a hospitality partner.',
        ];

        foreach (array_slice($commentTexts, 0, 3) as $offset => $content) {
            $comment = Comment::query()->firstOrCreate([
                'post_id' => $post->id,
                'user_id' => $users[($index + $offset) % $users->count()]->id,
                'content' => $content,
            ], [
                'status' => ContentStatus::Approved->value,
            ]);

            if ($offset < 2) {
                Comment::query()->firstOrCreate([
                    'post_id' => $post->id,
                    'parent_id' => $comment->id,
                    'user_id' => $users[($index + $offset + 1) % $users->count()]->id,
                    'content' => 'Good point. I will add that to the next revision.',
                ], [
                    'status' => ContentStatus::Approved->value,
                ]);
            }
        }
    }
}
