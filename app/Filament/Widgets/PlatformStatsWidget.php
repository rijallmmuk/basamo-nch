<?php

namespace App\Filament\Widgets;

use App\Models\Nagari;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $nagariId = $user?->isNagariAdmin() ? $user->nagari_id : null;

        $wargaQuery = User::whereIn('role', ['warga', 'umkm_owner'])
            ->when($nagariId, fn ($q) => $q->where('nagari_id', $nagariId));

        $umkmQuery = UmkmProfile::query()
            ->when($nagariId, fn ($q) => $q->where('nagari_id', $nagariId));

        $pendingProducts = UmkmProduct::where('status', 'pending')
            ->when($nagariId, fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('nagari_id', $nagariId)))
            ->count();

        $stats = [];

        // Total nagari hanya relevan untuk super_admin (lihat semua).
        if (! $nagariId) {
            $stats[] = Stat::make('Nagari', Nagari::count())
                ->description('Total nagari terdaftar')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary');
        }

        $stats[] = Stat::make('Warga', $wargaQuery->count())
            ->description('Akun portal (warga & pemilik UMKM)')
            ->descriptionIcon('heroicon-m-users')
            ->color('info');

        $stats[] = Stat::make('UMKM', $umkmQuery->count())
            ->description('Profil usaha terdaftar')
            ->descriptionIcon('heroicon-m-building-storefront')
            ->color('success');

        $stats[] = Stat::make('Produk menunggu', $pendingProducts)
            ->description('Perlu verifikasi')
            ->descriptionIcon('heroicon-m-clock')
            ->color($pendingProducts > 0 ? 'warning' : 'gray');

        return $stats;
    }
}
