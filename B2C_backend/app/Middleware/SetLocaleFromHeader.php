<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    private const SUPPORTED = ['en', 'ko', 'zh'];

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Accept-Language', 'en');

        // Accept-Language may look like "ko-KR,ko;q=0.9,en;q=0.8"
        $primary = strtolower(trim(explode(',', $header)[0]));
        $primary = explode(';', $primary)[0];
        $primary = explode('-', $primary)[0];
        $primary = trim($primary);

        if (in_array($primary, self::SUPPORTED, true)) {
            App::setLocale($primary);
        }

        return $next($request);
    }
}
