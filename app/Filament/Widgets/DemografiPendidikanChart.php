<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\DemografiData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DemografiPendidikanChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?string $chartId = 'demografiPendidikanChart';

    protected static ?string $heading = 'Pendidikan Terakhir Penduduk';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected function getSubheading(): ?string
    {
        return 'Penduduk terdata di '.$this->namaNagari().'.';
    }

    protected function getOptions(): array
    {
        return DemografiData::pendidikan($this->nagariId());
    }
}
