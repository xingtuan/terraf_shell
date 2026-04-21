<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);
        $content = collect(fake()->paragraphs(4))->implode("\n\n");

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'content' => $content,
            'content_json' => null,
            'excerpt' => Str::limit(strip_tags($content), 180),
            'cover_image_url' => null,
            'cover_image_path' => null,
            'reading_time' => 1,
            'status' => ContentStatus::Approved->value,
            'is_pinned' => false,
            'is_featured' => false,
            'engagement_score' => 0,
            'trending_score' => 0,
            'views_count' => 0,
            'featured_at' => null,
            'featured_by' => null,
            'published_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::Pending->value,
            'published_at' => null,
        ]);
    }
}
