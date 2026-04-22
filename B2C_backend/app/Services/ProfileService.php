<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function update(User $user, array $data): User
    {
        $emailChanged = array_key_exists('email', $data) && $data['email'] !== $user->email;

        $updatedUser = DB::transaction(function () use ($user, $data, $emailChanged): User {
            $user->fill(Arr::only($data, ['name', 'username', 'email']));

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            $profile = $user->profile()->firstOrCreate();
            $profileData = Arr::only($data, [
                'bio',
                'location',
                'website',
                'school_or_company',
                'region',
                'portfolio_url',
                'open_to_collab',
            ]);
            $previousAvatarPath = $profile->avatar_path;

            if (array_key_exists('avatar', $data) && $data['avatar'] !== null) {
                $this->mediaService->deletePath($profile->avatar_path);

                $upload = $this->mediaService->storeAvatar($data['avatar'], $user);
                $profileData = [...$profileData, ...$upload];
            } elseif (array_key_exists('avatar_path', $data)) {
                $profileData['avatar_path'] = filled($data['avatar_path'])
                    ? trim((string) $data['avatar_path'])
                    : null;
            }

            $profile->fill($profileData);
            $profile->save();

            if (
                array_key_exists('avatar_path', $profileData)
                && filled($previousAvatarPath)
                && $previousAvatarPath !== $profile->avatar_path
            ) {
                DB::afterCommit(function () use ($previousAvatarPath): void {
                    $this->mediaService->deletePath($previousAvatarPath);
                });
            }

            return $user->fresh()->load('profile');
        });

        if ($emailChanged) {
            $updatedUser->sendEmailVerificationNotification();
        }

        return $updatedUser;
    }
}
