<?php

namespace Database\Factories;

use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationLog>
 */
class ModerationLogFactory extends Factory
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
            'subject_type' => 'post',
            'subject_id' => Post::factory(),
            'action' => 'approved',
            'reason' => fake()->sentence(8),
            'metadata' => [
                'source' => 'factory',
            ],
        ];
    }
}
