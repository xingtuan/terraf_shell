<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'post' => Post::class,
            'comment' => Comment::class,
            'user' => User::class,
            'report' => Report::class,
        ]);

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin());

        RateLimiter::for('auth', fn ($request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for(
            'password-reset',
            fn ($request): Limit => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())
        );
        RateLimiter::for(
            'verification',
            fn ($request): Limit => Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip()))
        );
    }
}
