<?php

namespace App\Middleware;

use App\Services\Settings\SettingsService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRuntimeSettingEnabled
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next, string $key, string $default = 'true'): Response
    {
        $enabled = $this->settings->boolean($key, filter_var($default, FILTER_VALIDATE_BOOLEAN));

        if (! $enabled) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(__('api.errors.feature_disabled'), [], 403);
            }

            abort(403, __('api.errors.feature_disabled'));
        }

        return $next($request);
    }
}
