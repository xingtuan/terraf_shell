<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationEmailRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $authService): JsonResponse
    {
        [$user, $token] = $authService->register($request->validated(), $request->userAgent());

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->setAttribute('show_private', true)),
        ], 'Registration successful.', 201);
    }

    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        [$user, $token] = $authService->login($request->validated(), $request->userAgent());

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->setAttribute('show_private', true)),
        ], 'Login successful.');
    }

    public function logout(Request $request, AuthService $authService): JsonResponse
    {
        $authService->logout($request->user());

        return $this->successResponse(null, 'Logout successful.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile');

        return $this->successResponse(new UserResource($user));
    }

    public function forgotPassword(ForgotPasswordRequest $request, AuthService $authService): JsonResponse
    {
        $authService->sendPasswordResetLink($request->validated());

        return $this->successResponse(
            null,
            'If the account exists, a password reset link has been sent.'
        );
    }

    public function resetPassword(ResetPasswordRequest $request, AuthService $authService): JsonResponse
    {
        $authService->resetPassword($request->validated());

        return $this->successResponse(null, 'Password reset successful.');
    }

    public function resendVerificationEmail(
        ResendVerificationEmailRequest $request,
        AuthService $authService
    ): JsonResponse {
        $authService->sendVerificationNotification($request->user());

        return $this->successResponse(null, 'Verification email sent.');
    }

    public function verifyEmail(
        Request $request,
        int $id,
        string $hash,
        AuthService $authService
    ): JsonResponse|RedirectResponse {
        $user = $authService->verifyEmail($id, $hash);

        if ($request->expectsJson() || $request->wantsJson()) {
            return $this->successResponse(
                new UserResource($user->setAttribute('show_private', true)),
                'Email verified successfully.'
            );
        }

        $redirectUrl = $this->frontendUrl(
            (string) config('services.frontend.email_verification_path', '/email-verified'),
            ['status' => 'verified']
        );

        if ($redirectUrl !== null) {
            return redirect()->away($redirectUrl);
        }

        return $this->successResponse(
            new UserResource($user->setAttribute('show_private', true)),
            'Email verified successfully.'
        );
    }

    public function showResetPassword(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $redirectUrl = $this->frontendUrl(
            (string) config('services.frontend.password_reset_path', '/reset-password'),
            [
                'token' => $token,
                'email' => (string) $request->query('email'),
            ]
        );

        if ($redirectUrl !== null) {
            return redirect()->away($redirectUrl);
        }

        return $this->successResponse(
            [
                'token' => $token,
                'email' => (string) $request->query('email'),
            ],
            'Use this token and email with POST /api/auth/reset-password.'
        );
    }

    private function frontendUrl(string $path, array $query = []): ?string
    {
        $baseUrl = rtrim((string) config('services.frontend.url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        $url = $baseUrl.'/'.ltrim($path, '/');
        $query = array_filter($query, fn (mixed $value): bool => filled($value));

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }
}
