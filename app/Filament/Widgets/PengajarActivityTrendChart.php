<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\PengajarActivityTrendData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PengajarActivityTrendChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'pengajarActivityTrendChart';

    protected static ?string $heading = 'Aktivitas Belajar 4 Minggu Terakhir';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('pengajar');
    }

    protected function getOptions(): array
    {
        $user = auth()->user();

        return $user ? PengajarActivityTrendData::options($user) : [];
    }
}
