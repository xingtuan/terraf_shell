<?php

namespace Database\Factories;

use App\Models\AdminActionLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminActionLog>
 */
class AdminActionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'target_user_id' => User::factory(),
            'subject_type' => 'post',
            'subject_id' => Post::factory(),
            'action' => 'post.status_updated',
            'description' => fake()->sentence(8),
            'metadata' => [
                'source' => 'factory',
            ],
        ];
    }
}
