<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\PengajarModuleProgressData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PengajarModuleProgressChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pengajarModuleProgressChart';

    protected static ?string $heading = 'Status Belajar Warga per Modul';

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

        return $user ? PengajarModuleProgressData::options($user) : [];
    }
}
