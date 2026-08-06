<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\UmkmKunjunganData;
use App\Support\Dashboard\UmkmTrenKunjunganData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class UmkmTrenKunjunganChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'umkmTrenKunjunganChart';

    protected static ?string $heading = 'Tren Kunjungan 30 Hari';

    protected static ?string $subheading = 'Etalase usaha dan detail produk, dihitung sekali per pengunjung per hari.';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->usesUmkmSelfService() && $user->umkmProfile()->exists());
    }

    /**
     * Rekap harian baru berjalan sejak fitur ini dipasang. Sebelum ada isinya,
     * grafik datar akan terbaca sebagai "tidak ada pengunjung" padahal yang benar
     * adalah "belum ada yang dicatat", jadi keterangannya diganti.
     */
    protected function getSubheading(): ?string
    {
        $profile = auth()->user()?->umkmProfile;

        if ($profile && ! UmkmKunjunganData::adaRekap($profile)) {
            return 'Pencatatan harian baru dimulai. Grafik terisi begitu ada pengunjung.';
        }

        return static::$subheading;
    }

    protected function getOptions(): array
    {
        $user = auth()->user();

        return $user ? UmkmTrenKunjunganData::options($user) : [];
    }
}
