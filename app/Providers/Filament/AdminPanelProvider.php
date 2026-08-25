<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Auth;
use Filament\Enums\UserMenuPosition;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->authMiddleware([
                'web',
                Authenticate::class,
                //EnsureEmailIsVerified::class,
                \App\Http\Middleware\VerifyRole::class . ':admin',
            ])
            ->colors([
                'primary' => [
                    50  => 'oklch(0.98 0.02 75)',
                    100 => 'oklch(0.95 0.05 75)',
                    200 => 'oklch(0.90 0.09 75)',
                    300 => 'oklch(0.84 0.14 75)',
                    400 => 'oklch(0.76 0.18 75)',
                    500 => 'oklch(0.68 0.20 75)',
                    600 => 'oklch(0.60 0.18 75)',
                    700 => 'oklch(0.52 0.15 75)',
                    800 => 'oklch(0.44 0.12 75)',
                    900 => 'oklch(0.36 0.09 75)',
                    950 => 'oklch(0.28 0.06 75)',
                ],

                'secondary' => [
                    50  => 'oklch(0.98 0.02 145)',
                    100 => 'oklch(0.95 0.05 145)',
                    200 => 'oklch(0.90 0.09 145)',
                    300 => 'oklch(0.84 0.14 145)',
                    400 => 'oklch(0.76 0.18 145)',
                    500 => 'oklch(0.68 0.20 145)',
                    600 => 'oklch(0.60 0.18 145)',
                    700 => 'oklch(0.52 0.15 145)',
                    800 => 'oklch(0.44 0.12 145)',
                    900 => 'oklch(0.36 0.09 145)',
                    950 => 'oklch(0.28 0.06 145)',
                ],

                'success' => [
                    50  => 'oklch(0.98 0.02 145)',
                    100 => 'oklch(0.95 0.05 145)',
                    200 => 'oklch(0.90 0.09 145)',
                    300 => 'oklch(0.84 0.14 145)',
                    400 => 'oklch(0.76 0.18 145)',
                    500 => 'oklch(0.68 0.20 145)',
                    600 => 'oklch(0.60 0.18 145)',
                    700 => 'oklch(0.52 0.15 145)',
                    800 => 'oklch(0.44 0.12 145)',
                    900 => 'oklch(0.36 0.09 145)',
                    950 => 'oklch(0.28 0.06 145)',
                ],

                'info' => [
                    50  => 'oklch(0.98 0.02 180)',
                    100 => 'oklch(0.95 0.04 180)',
                    200 => 'oklch(0.91 0.07 180)',
                    300 => 'oklch(0.85 0.10 180)',
                    400 => 'oklch(0.78 0.12 180)',
                    500 => 'oklch(0.70 0.13 180)',
                    600 => 'oklch(0.61 0.11 180)',
                    700 => 'oklch(0.53 0.09 180)',
                    800 => 'oklch(0.45 0.07 180)',
                    900 => 'oklch(0.37 0.05 180)',
                    950 => 'oklch(0.29 0.03 180)',
                ],

                'warning' => [
                    50  => 'oklch(0.98 0.02 75)',
                    100 => 'oklch(0.95 0.05 75)',
                    200 => 'oklch(0.91 0.09 75)',
                    300 => 'oklch(0.86 0.14 75)',
                    400 => 'oklch(0.81 0.18 75)',
                    500 => 'oklch(0.76 0.19 75)',
                    600 => 'oklch(0.69 0.18 70)',
                    700 => 'oklch(0.60 0.15 65)',
                    800 => 'oklch(0.50 0.12 60)',
                    900 => 'oklch(0.41 0.09 55)',
                    950 => 'oklch(0.31 0.06 50)',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //
            ])
            ->plugins([
              //
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->font('Poppins')
            ->spa(hasPrefetching: true)
            ->unsavedChangesAlerts()
            ->sidebarFullyCollapsibleOnDesktop()
           // ->brandLogo(asset('assets/images/logo.png'))
          //  ->brandLogoHeight('3.5rem')
            ->favicon(asset('assets/images/favicon.png'))
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->sidebarWidth('full');
    }
}
