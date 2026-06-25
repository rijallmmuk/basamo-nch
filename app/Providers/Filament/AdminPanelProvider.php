<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile;
use App\Filament\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureAdminPasswordChanged;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
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
            ->profile(EditProfile::class, isSimple: false)
            ->brandName('Basamo NCH')
            ->brandLogo(fn (): Htmlable => view('filament.brand'))
            ->brandLogoHeight('2.25rem')
            ->font('Plus Jakarta Sans')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            // Lonceng notifikasi: dipakai untuk melaporkan hasil impor warga yang
            // diproses di latar belakang (queue). Lihat App\Jobs\ImportWarga.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // Urutan grup lintas-peran. Hanya grup yang punya item tampil:
            //  super admin → LMS, UMKM (Kategori), Sistem (+ Desa di tingkat atas).
            //  admin desa  → UMKM, Pengaturan (+ Warga di tingkat atas; LMS disembunyikan).
            ->navigationGroups(['LMS', 'UMKM', 'Sistem', 'Pengaturan'])
            // Palet tunggal NCH untuk semua peran (Deep Blue). Tanpa pembedaan warna
            // per-peran — identitas peran cukup lewat chip di topbar. Ramp eksplisit
            // agar shade 600 (warna tombol solid Filament) = NCH Deep Blue #003857.
            ->colors([
                'primary' => [
                    50 => '#eef5fa',
                    100 => '#d2e4f0',
                    200 => '#a6c9e1',
                    300 => '#6fa6cd',
                    400 => '#3a7faf',
                    500 => '#1b4f72',
                    600 => '#003857',
                    700 => '#002d46',
                    800 => '#002338',
                    900 => '#001b2b',
                    950 => '#00111c',
                ],
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
            // Modal pemblokir ganti sandi saat admin masih pakai OTP awal (must_change_password).
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.force-password-change-hook')->render(),
            )
            ->authMiddleware([
                Authenticate::class,
                EnsureAdminPasswordChanged::class,
            ]);
    }
}
