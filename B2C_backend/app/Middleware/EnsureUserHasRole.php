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
            return ApiResponse::error('Authentication is required.', [], 401);
        }

        if (! $user->isActive()) {
            return ApiResponse::error(
                'Your account cannot access this area.',
                [
                    'user' => [$user->participationRestrictionReason() ?: 'This account is not active.'],
                ],
                403
            );
        }

        if (! $user->hasAnyRole($roles)) {
            return ApiResponse::error('You are not authorized to perform this action.', [], 403);
        }

        return $next($request);
    }
}
