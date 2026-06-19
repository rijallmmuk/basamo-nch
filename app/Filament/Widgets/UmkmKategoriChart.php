<?php

namespace App\Filament\Widgets;

use App\Models\UmkmProfile;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class UmkmKategoriChart extends ApexChartWidget
{
    protected static ?string $chartId = 'umkmKategoriChart';

    protected static ?string $heading = 'Sebaran Kategori UMKM';

    protected static ?int $sort = 3;

    protected function getOptions(): array
    {
        $user = auth()->user();

        $data = UmkmProfile::query()
            ->when($user?->isNagariAdmin(), fn ($q) => $q->where('umkm_profiles.nagari_id', $user->nagari_id))
            ->join('umkm_categories', 'umkm_categories.id', '=', 'umkm_profiles.umkm_category_id')
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
            'colors' => ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#14b8a6', '#6b7280'],
        ];
    }
}
