<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraph(),
            'status' => ContentStatus::Approved->value,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::Pending->value,
        ]);
    }
}
