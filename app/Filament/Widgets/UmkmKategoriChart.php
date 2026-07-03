<?php

namespace App\Filament\Widgets;

use App\Models\UmkmProduct;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class UmkmKategoriChart extends ApexChartWidget
{
    protected static ?string $chartId = 'umkmKategoriChart';

    protected static ?string $heading = 'Sebaran Kategori UMKM';

    protected static ?int $sort = 3;

    protected function getOptions(): array
    {
        $user = auth()->user();

        $data = UmkmProduct::query()
            ->join('umkm_profiles', 'umkm_profiles.id', '=', 'umkm_products.umkm_profile_id')
            ->when($user?->isDesaAdmin(), fn ($q) => $q->where('umkm_profiles.desa_id', $user->desa_id))
            ->join('umkm_categories', 'umkm_categories.id', '=', 'umkm_products.umkm_category_id')
            ->selectRaw('umkm_categories.nama as kategori, COUNT(*) as total')
            ->groupBy('umkm_categories.nama')
            ->orderByDesc('total')
            ->pluck('total', 'kategori');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $data->values()->all() ?: [1],
            'labels' => $data->keys()->all() ?: ['Belum ada data'],
            'legend' => [
                'position' => 'bottom',
                'labels' => ['fontFamily' => 'inherit'],
            ],
            // Palet kategori bernuansa NCH (tetap kontras antar-kategori).
            'colors' => ['#003857', '#d4ac0d', '#00572a', '#1b4f72', '#9dcbf4', '#735c00', '#54d280'],
        ];
    }
}
