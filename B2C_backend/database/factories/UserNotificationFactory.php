<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
class UserNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_user_id' => User::factory(),
            'actor_user_id' => User::factory(),
            'type' => NotificationType::Comment->value,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(8),
            'action_url' => '/posts/'.$this->faker->slug(),
            'target_type' => 'post',
            'target_id' => Post::factory(),
            'data' => [
                'title' => $this->faker->sentence(4),
                'body' => $this->faker->sentence(8),
                'message' => $this->faker->sentence(8),
            ],
            'is_read' => false,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
