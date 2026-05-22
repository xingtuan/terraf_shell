<?php

namespace App\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isParticipationRestricted()) {
            $reason = $user->participationRestrictionReason()
                ?: __('api.community.restriction_reason');

            return ApiResponse::error(
                $user->isBanned()
                    ? __('api.community.banned')
                    : __('api.community.restricted'),
                [
                    'user' => [$reason],
                ],
                403
            );
        }

        return $next($request);
    }
}
