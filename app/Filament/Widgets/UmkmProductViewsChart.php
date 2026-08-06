<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\UmkmProductViewsData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class UmkmProductViewsChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'umkmProductViewsChart';

    protected static ?string $heading = 'Kunjungan Produk';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->usesUmkmSelfService() && $user->umkmProfile()->exists());
    }

    protected function getOptions(): array
    {
        $user = auth()->user();

        return $user ? UmkmProductViewsData::options($user) : [];
    }
}
