<?php

namespace App\Providers\Filament;

use App\Models\Organization;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->passwordReset()
            ->tenant(Organization::class, 'slug', ownershipRelationship: 'organization')
            ->tenantMenu(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->renderHook(
                'panels::head.end',
                fn () => new HtmlString('<meta name="robots" content="noindex, nofollow">'),
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn () => new HtmlString('
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="/register-account" style="font-size: 0.875rem; color: rgb(var(--primary-600)); text-decoration: underline;">
                            還沒有帳號？立即註冊
                        </a>
                    </div>
                '),
            )
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
                \App\Http\Middleware\LogAdminActions::class,
            ])
            ->tenantMiddleware([
                \App\Http\Middleware\EnsureSubscription::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
