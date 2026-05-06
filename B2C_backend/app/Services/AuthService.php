<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailPayloadFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
        private readonly EmailPayloadFactory $emailPayloadFactory,
    ) {}

    private function generateUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');
        if ($base === '' || strlen($base) < 2) {
            $base = 'user';
        }
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $username = $attempt === 0
                ? $base
                : $base.'_'.Str::lower(Str::random(4));

            if (! User::where('username', $username)->exists()) {
                return $username;
            }
        }

        do {
            $username = $base.'_'.Str::lower(Str::random(8));
        } while (User::where('username', $username)->exists());

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

        $this->dispatchVerificationEmail($user, 'auth.email_verification');

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
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user instanceof User) {
            return;
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = $this->passwordResetUrl($user, $token);
        $log = $this->emailDispatchService->sendEventSafely(
            'auth.password_reset',
            $this->emailPayloadFactory->forUser($user, [
                'reset_url' => $resetUrl,
                'expires_minutes' => (int) config('auth.passwords.users.expire', 60),
            ]),
            [
                'related' => $user,
                'idempotency_key' => 'auth.password_reset:'.$user->id.':'.sha1($token),
            ],
        );

        if (! $log || in_array($log->status, ['skipped', 'failed'], true)) {
            Password::broker()->deleteToken($user);
        }
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

                $this->emailDispatchService->sendEventSafely(
                    'auth.password_reset_success',
                    $this->emailPayloadFactory->forUser($user),
                    [
                        'related' => $user,
                        'idempotency_key' => 'auth.password_reset_success:'.$user->id.':'.now()->timestamp,
                    ],
                );
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

        $this->dispatchVerificationEmail($user, 'auth.email_verification_resent');
    }

    public function verifyEmail(int $id, string $hash): User
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException('Invalid email verification link.');
        }

        $wasUnverified = ! $user->hasVerifiedEmail();

        if ($wasUnverified) {
            $user->markEmailAsVerified();
        }

        if ($wasUnverified) {
            $this->emailDispatchService->sendEventSafely(
                'auth.welcome',
                $this->emailPayloadFactory->forUser($user),
                ['related' => $user],
            );
        }

        return $user->fresh()->load('profile');
    }

    private function dispatchVerificationEmail(User $user, string $eventKey): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->emailDispatchService->sendEventSafely(
            $eventKey,
            $this->emailPayloadFactory->forUser($user, [
                'verification_url' => $verificationUrl,
            ]),
            [
                'related' => $user,
                'idempotency_key' => $eventKey.':'.$user->id.':'.$user->email,
            ],
        );
    }

    private function passwordResetUrl(User $user, string $token): string
    {
        $frontendUrl = rtrim((string) config('services.frontend.url'), '/');

        if ($frontendUrl !== '') {
            return $frontendUrl.'/'.ltrim((string) config('services.frontend.password_reset_path', '/reset-password'), '/')
                .'?'.http_build_query([
                    'token' => $token,
                    'email' => $user->email,
                ]);
        }

        return URL::route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
