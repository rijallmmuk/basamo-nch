<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\DemografiData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DemografiPekerjaanChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?string $chartId = 'demografiPekerjaanChart';

    protected static ?string $heading = 'Sepuluh Pekerjaan Terbanyak';

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
        return DemografiData::pekerjaan($this->nagariId());
    }
}
