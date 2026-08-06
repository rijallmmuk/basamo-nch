<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\StatistikIdm;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\OperatorDashboardData;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperatorMetricsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?int $sort = -1;

    protected function getHeading(): ?string
    {
        // Superadmin melihat kartu lintas nagari DAN kartu ini; tanpa judul,
        // keduanya terbaca sebagai angka yang sama.
        return 'Ringkasan '.$this->namaNagari();
    }

    protected function getStats(): array
    {
        $data = OperatorDashboardData::forNagari($this->nagari());
        $m = $data['metrics'];

        $belumBelajar = max(0, $m['akunWargaCount'] - $m['lmsActiveCount']);

        return [
            Stat::make('Warga Belajar', "{$m['lmsActiveCount']} Warga Aktif Belajar")
                // Yang belum tersentuh sama sekali adalah pekerjaan operator, jadi
                // angkanya disebut, bukan hanya yang sudah jalan.
                ->description("{$m['lmsCompletedCount']} modul selesai · {$belumBelajar} akun belum mulai · rata-rata evaluasi {$m['avgEvaluasiScore']} dari 100")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success')
                ->url(SlcRekapResource::getUrl('index')),

            Stat::make('UMKM Nagari', "{$m['umkmCount']} Usaha · {$m['productPublishedCount']} Produk")
                ->description("Etalase dilihat {$m['etalaseTotalViews']}× · produk {$m['productTotalViews']}×")
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning')
                ->url(UmkmProfileResource::getUrl('index')),

            Stat::make('SDGs & IDM Nagari', "Capaian SDGs {$m['sdgAvgScore']}")
                ->description("Status IDM Kemendesa: {$m['statusIdm']}")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary')
                // Rincian IKS/IKE/IKL sudah punya halaman sendiri; kartu ini menuju
                // ke sana alih-alih menggandakan isinya di dasbor.
                ->url(StatistikIdm::getUrl()),

            // Penduduk dan akun portal adalah dua populasi berbeda, jadi tidak
            // digabung dalam satu angka: pembagian laki-laki/perempuan berlaku untuk
            // penduduk, sedangkan jumlah akun disebut terpisah.
            Stat::make('Kependudukan Nagari', "{$m['pendudukCount']} Penduduk Terdata")
                ->description("{$m['priaCount']} laki-laki · {$m['wanitaCount']} perempuan · {$m['akunWargaCount']} punya akun portal")
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->url(PendudukResource::getUrl('index')),
        ];
    }
}
