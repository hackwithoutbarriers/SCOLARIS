<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\RoleActionCenter;
use App\Filament\Widgets\SchoolSupportOverview;
use App\Filament\Widgets\SuperAdminOverview;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordChange;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_BEFORE,
            fn (): string => is_file(public_path('build/manifest.json'))
                ? app(Vite::class)('resources/css/filament-admin-premium.css')->toHtml()
                : '',
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            fn (): string => $this->renderOptionalView('filament.partials.context-bar'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => $this->renderOptionalView('filament.partials.decorative-scope'),
        );
    }

    private function renderOptionalView(string $view): string
    {
        return is_file(resource_path('views/'.str_replace('.', '/', $view).'.blade.php'))
            ? view($view)->render()
            : '';
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->userMenuItems([
                MenuItem::make()->label('Français')->url(url('/language/fr')),
            ])
            ->login()
            ->registration(false)
            ->colors(['primary' => Color::hex('#173B67'), 'success' => Color::hex('#168A68'), 'warning' => Color::hex('#C98224')])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->widgets([
                RoleActionCenter::class,
                SuperAdminOverview::class,
                SchoolSupportOverview::class,
            ])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                AuthenticateSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class,
                SubstituteBindings::class, DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, EnsureActiveUser::class, EnsurePasswordChange::class]);
    }
}
