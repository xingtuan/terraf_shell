<?php

use App\Exceptions\CartStockLimitException;
use App\Middleware\EnsureRuntimeSettingEnabled;
use App\Middleware\EnsureUserHasRole;
use App\Middleware\EnsureUserNotBanned;
use App\Middleware\SetLocaleFromHeader;
use App\Support\ApiResponse;
use App\Support\FrontendUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(SetLocaleFromHeader::class);

        $middleware->alias([
            'not_banned' => EnsureUserNotBanned::class,
            'runtime_setting' => EnsureRuntimeSettingEnabled::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isBrowserEmailVerificationRequest = function (Request $request): bool {
            return $request->route()?->named('verification.verify') === true
                && ! $request->expectsJson()
                && ! $request->wantsJson();
        };

        $verificationRedirect = function (Request $request, string $status) {
            $redirectUrl = FrontendUrl::emailVerificationUrl(
                $status,
                (string) ($request->query('locale') ?: FrontendUrl::currentLocale()),
            );

            if ($redirectUrl === null) {
                return null;
            }

            return redirect()->away($redirectUrl);
        };

        $signatureFailureStatus = function (Request $request): string {
            $expires = $request->query('expires');

            if (is_numeric($expires) && (int) $expires <= now()->timestamp) {
                return 'expired';
            }

            return 'invalid';
        };

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $throwable) => $request->is('api/*')
        );

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) use ($isBrowserEmailVerificationRequest, $signatureFailureStatus, $verificationRedirect) {
            if (! $isBrowserEmailVerificationRequest($request)) {
                return null;
            }

            return $verificationRedirect($request, $signatureFailureStatus($request));
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof CartStockLimitException) {
                return ApiResponse::error(
                    $exception->getMessage(),
                    $exception->errors(),
                    $exception->status,
                    $exception->meta(),
                );
            }

            return ApiResponse::error(
                __('api.errors.validation_failed'),
                $exception->errors(),
                $exception->status
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(__('api.errors.authentication_required'), [], 401);
            }

            // Guests hitting an auth-protected web route (the /admin/* export endpoints in
            // routes/web.php) are sent to the Filament admin login. The framework default would
            // otherwise call route('login') — undefined in this app — throwing
            // RouteNotFoundException and surfacing a 500 instead of a clean redirect.
            return redirect()->guest(route('filament.admin.auth.login'));
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->route()?->named('verification.verify') === true
                && ! $request->expectsJson()
                && ! $request->wantsJson()) {
                $redirectUrl = FrontendUrl::emailVerificationUrl(
                    'invalid',
                    (string) ($request->query('locale') ?: FrontendUrl::currentLocale()),
                );

                return $redirectUrl === null ? null : redirect()->away($redirectUrl);
            }

            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                in_array($exception->getMessage(), ['', 'This action is unauthorized.'], true)
                    ? __('api.errors.forbidden')
                    : $exception->getMessage(),
                [],
                403
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($isBrowserEmailVerificationRequest, $verificationRedirect) {
            if ($isBrowserEmailVerificationRequest($request)) {
                return $verificationRedirect($request, 'invalid');
            }

            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(__('api.errors.not_found'), [], 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(__('api.errors.endpoint_not_found'), [], 404);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $message = match (true) {
                $status >= 500 => __('api.errors.server_error'),
                $status === 401 => __('api.errors.authentication_required'),
                $status === 403 => in_array($exception->getMessage(), ['', 'This action is unauthorized.'], true)
                    ? __('api.errors.forbidden')
                    : $exception->getMessage(),
                $status === 404 => __('api.errors.not_found'),
                $status === 429 => __('api.errors.rate_limited'),
                default => $exception->getMessage() ?: __('api.errors.request_failed'),
            };

            return ApiResponse::error($message, [], $status);
        });
    })->create();
