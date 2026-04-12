<?php

namespace Database\Factories;

use App\Enums\UserViolationSeverity;
use App\Enums\UserViolationStatus;
use App\Enums\UserViolationType;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserViolation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserViolation>
 */
class UserViolationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'actor_user_id' => User::factory(),
            'resolved_by' => null,
            'report_id' => null,
            'subject_type' => 'post',
            'subject_id' => Post::factory(),
            'type' => UserViolationType::ManualWarning->value,
            'severity' => UserViolationSeverity::Warning->value,
            'status' => UserViolationStatus::Open->value,
            'reason' => fake()->sentence(8),
            'resolution_note' => null,
            'metadata' => [
                'source' => 'factory',
            ],
            'occurred_at' => now(),
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => UserViolationStatus::Resolved->value,
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
            'resolution_note' => 'Resolved during review.',
        ]);
    }

    public function withReport(): static
    {
        return $this->state(fn (): array => [
            'report_id' => Report::factory(),
        ]);
    }
}
