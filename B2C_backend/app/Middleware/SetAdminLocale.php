<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ko', 'zh'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->session()->get('admin_locale', config('app.locale', 'en'));

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
