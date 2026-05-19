<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'target_type' => 'post',
            'target_id' => Post::factory(),
            'reason' => 'Spam or misleading information',
            'description' => $this->faker->sentence(12),
            'status' => ReportStatus::Pending->value,
            'moderator_note' => null,
            'public_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'resolved_at' => null,
            'dismissed_at' => null,
            'completed_at' => null,
            'reporter_notified_at' => null,
            'resolution_action' => null,
        ];
    }
}
