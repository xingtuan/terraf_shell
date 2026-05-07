<?php

namespace App\Providers\Filament;

use App\Filament\Support\AdminNavigationGroup;
use App\Middleware\SetAdminLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn (): string => __('admin.brand.name'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->navigationGroups(AdminNavigationGroup::class)
            ->userMenuItems([
                Action::make('language_en')
                    ->label(fn (): string => __('admin.locale.english'))
                    ->icon('heroicon-o-language')
                    ->url(fn (): string => route('admin.locale.switch', ['locale' => 'en']))
                    ->sort(10),
                Action::make('language_ko')
                    ->label(fn (): string => __('admin.locale.korean'))
                    ->icon('heroicon-o-language')
                    ->url(fn (): string => route('admin.locale.switch', ['locale' => 'ko']))
                    ->sort(11),
                Action::make('language_zh')
                    ->label(fn (): string => __('admin.locale.chinese'))
                    ->icon('heroicon-o-language')
                    ->url(fn (): string => route('admin.locale.switch', ['locale' => 'zh']))
                    ->sort(12),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetAdminLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
