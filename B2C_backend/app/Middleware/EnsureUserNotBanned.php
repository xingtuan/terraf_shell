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
                ?: 'A moderator has restricted this account.';

            return ApiResponse::error(
                $user->isBanned()
                    ? 'Your account has been banned from community actions.'
                    : 'Your account is restricted from community actions.',
                [
                    'user' => [$reason],
                ],
                403
            );
        }

        return $next($request);
    }
}
