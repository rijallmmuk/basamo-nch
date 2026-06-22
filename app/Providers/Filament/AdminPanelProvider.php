<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Http\Middleware\EnsureAdminPasswordChanged;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->profile(isSimple: false)
            ->brandName('Basamo NCH')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            ->navigationGroups(['LMS', 'UMKM', 'Pengaturan'])
            // Palet dasar = Admin Desa (Teal). super_admin di-override ke Indigo via render hook
            // (lihat superAdminThemeOverride) sebagai pembeda peran.
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                // Sembunyikan menu "Role" dari navigasi — 4 role tetap & dikelola di kode.
                // Resource masih bisa diakses via URL bila perlu.
                FilamentShieldPlugin::make()
                    ->registerNavigation(false),
                FilamentApexChartsPlugin::make(),
            ])
            // Chip identitas peran di topbar (super_admin vs admin desa + nama desa).
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.topbar-role-badge')->render(),
            )
            // Aksen tema per-peran: super_admin → Indigo (override --primary).
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => static::superAdminThemeOverride(),
            )
            ->authMiddleware([
                Authenticate::class,
                EnsureAdminPasswordChanged::class,
            ]);
    }

    /**
     * super_admin memakai aksen Indigo (override variabel --primary) agar berbeda
     * jelas dari Admin Desa yang memakai Teal. Indigo tetap warna vibran sehingga
     * tombol solid tetap berteks putih (kontras aman).
     */
    protected static function superAdminThemeOverride(): string
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return '';
        }

        $vars = collect(Color::Indigo)
            ->map(fn (string $value, int|string $shade): string => "--primary-{$shade}:{$value};")
            ->implode('');

        return '<style id="super-admin-theme">:root{'.$vars.'}</style>';
    }
}
