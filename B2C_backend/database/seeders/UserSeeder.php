<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => UserRole::Admin->value,
                'account_status' => AccountStatus::Active->value,
                'is_banned' => false,
                'banned_at' => null,
                'restricted_at' => null,
                'ban_reason' => null,
                'restriction_reason' => null,
                'remember_token' => Str::random(10),
            ]
        );
        $admin->profile()->firstOrCreate();

        $moderator = User::query()->updateOrCreate(
            ['username' => 'moderator'],
            [
                'name' => 'Moderator User',
                'email' => 'moderator@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => UserRole::Moderator->value,
                'account_status' => AccountStatus::Active->value,
                'is_banned' => false,
                'banned_at' => null,
                'restricted_at' => null,
                'ban_reason' => null,
                'restriction_reason' => null,
                'remember_token' => Str::random(10),
            ]
        );
        $moderator->profile()->firstOrCreate();

        User::factory()->count(8)->create();

        $bannedUser = User::query()->updateOrCreate(
            ['username' => 'blockedmember'],
            [
                'name' => 'Blocked Member',
                'email' => 'banned@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => UserRole::Creator->value,
                'account_status' => AccountStatus::Banned->value,
                'is_banned' => true,
                'banned_at' => now(),
                'restricted_at' => null,
                'ban_reason' => 'Repeated violations of the community rules.',
                'restriction_reason' => null,
                'remember_token' => Str::random(10),
            ]
        );
        $bannedUser->profile()->firstOrCreate();
    }
}
