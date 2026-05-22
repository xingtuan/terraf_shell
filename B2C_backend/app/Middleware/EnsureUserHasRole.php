<?php

namespace App\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error(__('api.errors.authentication_required'), [], 401);
        }

        if (! $user->isActive()) {
            return ApiResponse::error(
                __('api.auth.account_inactive'),
                [
                    'user' => [$user->participationRestrictionReason() ?: __('api.auth.account_not_active')],
                ],
                403
            );
        }

        if (! $user->hasAnyRole($roles)) {
            return ApiResponse::error(__('api.errors.forbidden'), [], 403);
        }

        return $next($request);
    }
}
