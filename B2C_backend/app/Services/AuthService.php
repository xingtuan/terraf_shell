<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private function generateUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');
        if ($base === '' || strlen($base) < 2) {
            $base = 'user';
        }
        $username = $base;
        $attempt = 0;
        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . Str::lower(Str::random(4));
            $attempt++;
            if ($attempt > 10) {
                $username = $base . '_' . Str::lower(Str::random(8));
                break;
            }
        }

        return $username;
    }

    public function register(array $data, ?string $userAgent = null): array
    {
        [$user, $token] = DB::transaction(function () use ($data, $userAgent): array {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $this->generateUsername($data['email']),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'] ?? UserRole::Creator->value,
                'account_status' => AccountStatus::Active->value,
            ]);

            $user->profile()->create();

            $token = $user->createToken(
                $data['device_name'] ?? $userAgent ?? 'web'
            )->plainTextToken;

            return [$user->load('profile'), $token];
        });

        $user->sendEmailVerificationNotification();

        return [$user, $token];
    }

    public function login(array $credentials, ?string $userAgent = null): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.invalid_credentials')],
            ]);
        }

        if ($user->isBanned()) {
            throw new AuthorizationException(__('auth.account_banned'));
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? $userAgent ?? 'web'
        )->plainTextToken;

        return [$user->load('profile'), $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function sendPasswordResetLink(array $data): void
    {
        Password::sendResetLink([
            'email' => $data['email'],
        ]);
    }

    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            $data,
            function (User $user) use ($data): void {
                $user->forceFill([
                    'password' => $data['password'],
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    public function sendVerificationNotification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }

    public function verifyEmail(int $id, string $hash): User
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException('Invalid email verification link.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user->fresh()->load('profile');
    }
}
