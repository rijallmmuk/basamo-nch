<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Profil;
use App\Http\Middleware\EnsureAdminPasswordChanged;
use App\Http\Middleware\EnsureAdminSessionTracked;
use App\Http\Middleware\EnsureNagariSiteMatchesUser;
use App\Support\Filament\PanelIdentity;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Hammadzafar05\MobileBottomNav\MobileBottomNav;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class BackofficePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            // id, path, dan nama route seragam 'panel' (netral untuk semua peran
            // back-office + pemilik UMKM; bukan cuma "admin"). Route = filament.panel.*.
            ->id('panel')
            ->path('panel')
            ->viteTheme('resources/css/filament/panel/theme.css')
            // Login bawaan Filament dimatikan — semua peran login lewat halaman gabungan
            // (route `login`). Tamu yang membuka /panel diarahkan ke sana oleh auth handler.
            // Profil bawaan Filament diganti halaman kustom (read-only + modal Ubah) → App\Filament\Pages\Profil.
            // Key 'profile' (bukan 'profil') sengaja: menimpa item akun default Filament yang
            // memunculkan header nama di dropdown → dropdown cukup "Profil" + "Keluar".
            ->userMenuItems([
                'portal' => Action::make('portal')
                    ->label('Portal Belajar Warga')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (): string => route('portal.home'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->usesUmkmSelfService())
                    ->extraAttributes(['@click.prevent' => "\$dispatch('open-switch-portal-modal')"])
                    ->sort(1),
                'profile' => Action::make('profile')
                    ->label('Profil')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => Profil::getUrl())
                    ->sort(2),
                // "Keluar" merah — closure menerima item logout bawaan (URL + POST tetap
                // utuh), hanya warnanya diubah jadi danger.
                'logout' => fn (Action $action): Action => $action->color('danger')->sort(3),
            ])
            ->navigationItems([
                NavigationItem::make('Portal Belajar Warga')
                    ->label('Portal Belajar Warga')
                    ->url(fn (): string => route('portal.home'))
                    ->icon('heroicon-o-academic-cap')
                    ->group('Portal Warga')
                    ->visible(fn (): bool => auth()->check() && auth()->user()->usesUmkmSelfService())
                    ->extraAttributes(['@click.prevent' => "\$dispatch('open-switch-portal-modal')"])
                    ->sort(1),
            ])
            ->brandName('Basamo NCH Smart Learning Center')
            ->favicon(asset('favicon.ico'))
            ->brandLogo(fn (): Htmlable => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->font('Plus Jakarta Sans')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            // Palet tunggal NCH (mode terang) → tanpa switcher tema di menu pengguna.
            ->darkMode(false)
            // TANPA lonceng notifikasi di panel (keputusan: admin/super admin cukup pakai
            // badge angka pada menu sidebar — UMKM & Diskusi). databaseNotifications bawaan
            // Filament tidak dipakai.
            // Urutan grup lintas-peran (2026-07-14, dirapikan): "Nagari" (data fondasi —
            // wilayah & warga, prasyarat 4 pilar) dulu, lalu 4 pilar program persis
            // urutan resmi pilar ("LMS · SDGs · UMKM · IoT"), baru grup penunjang
            // (Situs Publik, Sistem), lalu Bantuan paling bawah. Dasbor SENDIRI di luar grup mana pun.
            //  operator nagari  → Nagari (Profil Nagari, Warga), LMS disembunyikan,
            //                 SDGs Desa, UMKM, Smart IoT (Cuaca); Situs Publik & Sistem
            //                 disembunyikan.
            //  super admin → Nagari (Warga — sejak "kuasa penuh" 2026-07-14),
            //                 LMS, SDGs Desa, UMKM (+Kategori), Smart IoT (Cuaca saja —
            //                 lapisan data sensor dibuang 2026-07-29), Situs Publik (FAQ + Kontak Masuk
            //                 beranda base URL — konten platform, bukan per-nagari), Sistem.
            ->navigationGroups(['Nagari', 'SLC', 'UMKM', 'Status Desa', 'Smart IoT', 'Situs Publik', 'Sistem', 'Portal Warga', 'Bantuan'])
            // Konten dilebarkan (2026-07-13) — max-width 7xl bawaan Filament dicopot;
            // margin kiri-kanan halaman diperkecil lewat theme.css (.fi-main).
            ->maxContentWidth(Width::Full)
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
            ->persistentMiddleware([
                AuthenticateSession::class,
            ])
            ->plugins([
                FilamentApexChartsPlugin::make(),
                // Navigasi bawah ala aplikasi di ponsel (mayoritas warga/pemilik UMKM
                // pakai HP). Auto-ambil item nav teratas; desktop tetap sidebar.
                MobileBottomNav::make()
                    ->fromNavigation(limit: 5),
            ])
            // Nama lengkap dan peran tampil di samping avatar; menu akun bawaan tetap utuh.
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.topbar-role-badge', PanelIdentity::forUser(auth()->user()))->render(),
            )
            // Sembunyikan tautan Portal Warga dari mobile bottom navigation bar secara mutlak.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>.fi-bottom-nav .fi-bottom-nav-item[href*="/portal"], .fi-bottom-nav a[href*="/portal"] { display: none !important; }</style>',
            )
            // Modal pemblokir saat admin masih memakai password awal bersama & modal konfirmasi beralih ke portal.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.force-password-change-hook')->render().view('filament.switch-portal-modal')->render(),
            )
            ->authMiddleware([
                Authenticate::class,
                EnsureNagariSiteMatchesUser::class,
                EnsureAdminSessionTracked::class,
                EnsureAdminPasswordChanged::class,
            ], isPersistent: true);
    }
}
