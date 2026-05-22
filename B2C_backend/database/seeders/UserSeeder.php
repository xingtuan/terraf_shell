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

        foreach ($this->initialCreators() as $creator) {
            $user = User::query()->updateOrCreate(
                ['username' => $creator['username']],
                [
                    'name' => $creator['name'],
                    'email' => $creator['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'role' => UserRole::Creator->value,
                    'account_status' => AccountStatus::Active->value,
                    'is_banned' => false,
                    'banned_at' => null,
                    'restricted_at' => null,
                    'ban_reason' => null,
                    'restriction_reason' => null,
                    'remember_token' => Str::random(10),
                ]
            );

            $user->profile()->updateOrCreate([], $creator['profile']);
        }

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

    /**
     * @return array<int, array{name: string, username: string, email: string, profile: array<string, mixed>}>
     */
    private function initialCreators(): array
    {
        return [
            [
                'name' => 'Ava Williams',
                'username' => 'avawilliams',
                'email' => 'ava.williams@example.com',
                'profile' => [
                    'bio' => 'Product designer exploring circular material applications.',
                    'school_or_company' => 'Auckland Design Lab',
                    'region' => 'Auckland, New Zealand',
                    'location' => 'Auckland, New Zealand',
                    'portfolio_url' => 'https://example.com/ava',
                    'website' => 'https://example.com/ava',
                    'open_to_collab' => true,
                ],
            ],
            [
                'name' => 'Noah Chen',
                'username' => 'noahchen',
                'email' => 'noah.chen@example.com',
                'profile' => [
                    'bio' => 'Materials researcher focused on low-waste manufacturing.',
                    'school_or_company' => 'Pacific Materials Studio',
                    'region' => 'Wellington, New Zealand',
                    'location' => 'Wellington, New Zealand',
                    'portfolio_url' => 'https://example.com/noah',
                    'website' => 'https://example.com/noah',
                    'open_to_collab' => true,
                ],
            ],
            [
                'name' => 'Mia Patel',
                'username' => 'miapatel',
                'email' => 'mia.patel@example.com',
                'profile' => [
                    'bio' => 'Hospitality operator testing durable serviceware concepts.',
                    'school_or_company' => 'Harbour Table Group',
                    'region' => 'Tauranga, New Zealand',
                    'location' => 'Tauranga, New Zealand',
                    'portfolio_url' => 'https://example.com/mia',
                    'website' => 'https://example.com/mia',
                    'open_to_collab' => false,
                ],
            ],
            [
                'name' => 'Lucas Brown',
                'username' => 'lucasbrown',
                'email' => 'lucas.brown@example.com',
                'profile' => [
                    'bio' => 'Industrial designer prototyping modular fixtures.',
                    'school_or_company' => 'Civic Workshop',
                    'region' => 'Christchurch, New Zealand',
                    'location' => 'Christchurch, New Zealand',
                    'portfolio_url' => 'https://example.com/lucas',
                    'website' => 'https://example.com/lucas',
                    'open_to_collab' => true,
                ],
            ],
            [
                'name' => 'Sophie Kim',
                'username' => 'sophiekim',
                'email' => 'sophie.kim@example.com',
                'profile' => [
                    'bio' => 'Community maker documenting small-batch prototypes.',
                    'school_or_company' => 'Maker Commons',
                    'region' => 'Hamilton, New Zealand',
                    'location' => 'Hamilton, New Zealand',
                    'portfolio_url' => 'https://example.com/sophie',
                    'website' => 'https://example.com/sophie',
                    'open_to_collab' => true,
                ],
            ],
            [
                'name' => 'Ethan Wilson',
                'username' => 'ethanwilson',
                'email' => 'ethan.wilson@example.com',
                'profile' => [
                    'bio' => 'Engineer evaluating fixture performance and repairability.',
                    'school_or_company' => 'South Island Fabrication',
                    'region' => 'Dunedin, New Zealand',
                    'location' => 'Dunedin, New Zealand',
                    'portfolio_url' => 'https://example.com/ethan',
                    'website' => 'https://example.com/ethan',
                    'open_to_collab' => false,
                ],
            ],
            [
                'name' => 'Olivia Taylor',
                'username' => 'oliviataylor',
                'email' => 'olivia.taylor@example.com',
                'profile' => [
                    'bio' => 'Retail buyer comparing sustainable display materials.',
                    'school_or_company' => 'Northline Retail',
                    'region' => 'Napier, New Zealand',
                    'location' => 'Napier, New Zealand',
                    'portfolio_url' => 'https://example.com/olivia',
                    'website' => 'https://example.com/olivia',
                    'open_to_collab' => true,
                ],
            ],
            [
                'name' => 'Liam Nguyen',
                'username' => 'liamnguyen',
                'email' => 'liam.nguyen@example.com',
                'profile' => [
                    'bio' => 'Student maker testing circular design workflows.',
                    'school_or_company' => 'OXP Student Studio',
                    'region' => 'Queenstown, New Zealand',
                    'location' => 'Queenstown, New Zealand',
                    'portfolio_url' => 'https://example.com/liam',
                    'website' => 'https://example.com/liam',
                    'open_to_collab' => true,
                ],
            ],
        ];
    }
}
