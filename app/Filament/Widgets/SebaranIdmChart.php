<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\PlatformIdmData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SebaranIdmChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'sebaranIdmChart';

    protected static ?string $heading = 'Sebaran Status IDM Nagari Mitra';

    protected static ?string $subheading = 'Status tahun terbaru tiap nagari aktif menurut Kemendesa.';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasAnyRole(['superadmin', 'dpmd'])
            && $user?->managedNagariId() === null;
    }

    protected function getOptions(): array
    {
        return PlatformIdmData::sebaranIdm();
    }
}
