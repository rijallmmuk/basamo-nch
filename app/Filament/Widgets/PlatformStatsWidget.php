<?php

namespace App\Filament\Widgets;

use App\Enums\UmkmProductStatus;
use App\Models\Desa;
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
        $desaId = $user?->isDesaAdmin() ? $user->desa_id : null;

        $wargaQuery = User::where('role', 'warga')
            ->when($desaId, fn ($q) => $q->where('desa_id', $desaId));

        $umkmQuery = UmkmProfile::query()
            ->when($desaId, fn ($q) => $q->where('desa_id', $desaId));

        $pendingProducts = UmkmProduct::where('status', UmkmProductStatus::Pending)
            ->when($desaId, fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('desa_id', $desaId)))
            ->count();

        $stats = [];

        // Total desa hanya relevan untuk super_admin (lihat semua).
        if (! $desaId) {
            $stats[] = Stat::make('Desa', Desa::count())
                ->description('Total desa terdaftar')
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
