<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Nagaris\NagariResource;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\KontakMasuk;
use App\Support\Dashboard\SuperadminDashboardData;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperadminMetricsWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasAnyRole(['superadmin', 'dpmd'])
            && $user?->managedNagariId() === null;
    }

    protected function getStats(): array
    {
        $data = SuperadminDashboardData::overview();
        $m = $data['metrics'];

        $laporanBaru = KontakMasuk::whereNull('balasan')->count();

        return [
            Stat::make('Nagari Mitra', "{$m['nagariCount']} Nagari Aktif")
                ->description("{$m['kabupatenCount']} kabupaten di Sumatra Barat")
                ->descriptionIcon('heroicon-m-map')
                ->color('primary')
                ->chart([7, 10, 15, $m['nagariCount']]) // Sparkline sederhana
                ->url(NagariResource::getUrl('index')),

            Stat::make('Kependudukan', number_format($m['pendudukCount'], 0, ',', '.').' Penduduk')
                ->description(number_format($m['wargaCount'], 0, ',', '.').' punya akun portal')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([100, 250, 400, $m['pendudukCount']])
                ->url(PendudukResource::getUrl('index')),

            Stat::make('Pembelajaran', "{$m['modulSelesaiCount']} Modul Selesai")
                ->description('Seluruh nagari mitra')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success')
                ->chart([5, 12, 30, $m['modulSelesaiCount']])
                ->url(SlcRekapResource::getUrl('index')),

            Stat::make('UMKM Nagari', "{$m['umkmCount']} Usaha")
                ->description("{$m['productCount']} produk, {$m['etalaseTotalViews']}× dilihat")
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning')
                ->chart([2, 8, 15, $m['umkmCount']])
                ->url(UmkmProfileResource::getUrl('index')),

            Stat::make('Laporan Masuk', "{$laporanBaru} Belum Dibalas")
                ->description('Menunggu tindak lanjut Superadmin')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($laporanBaru > 0 ? 'danger' : 'gray')
                ->chart([0, 0, 0, $laporanBaru])
                ->url(\App\Filament\Resources\KontakMasuks\KontakMasukResource::getUrl('index')),
        ];
    }
}
