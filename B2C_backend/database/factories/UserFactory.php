<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if (! $user->profile()->exists()) {
                Profile::factory()->create([
                    'user_id' => $user->id,
                ]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Creator->value,
            'account_status' => AccountStatus::Active->value,
            'is_banned' => false,
            'banned_at' => null,
            'restricted_at' => null,
            'ban_reason' => null,
            'restriction_reason' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin->value,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Moderator->value,
        ]);
    }

    public function visitor(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Visitor->value,
        ]);
    }

    public function smePartner(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::SmePartner->value,
        ]);
    }

    public function restricted(): static
    {
        return $this->state(fn () => [
            'account_status' => AccountStatus::Restricted->value,
            'is_banned' => false,
            'banned_at' => null,
            'restricted_at' => now(),
            'ban_reason' => null,
            'restriction_reason' => 'Limited due to moderation review.',
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn () => [
            'account_status' => AccountStatus::Banned->value,
            'is_banned' => true,
            'banned_at' => now(),
            'restricted_at' => null,
            'ban_reason' => 'Repeated violations of the community rules.',
            'restriction_reason' => null,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
