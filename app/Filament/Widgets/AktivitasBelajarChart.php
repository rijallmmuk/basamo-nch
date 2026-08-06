<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\AktivitasBelajarData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AktivitasBelajarChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?string $chartId = 'aktivitasBelajarChart';

    protected static ?string $heading = 'Aktivitas Belajar 30 Hari Terakhir';

    protected static ?int $sort = 6;

    protected function getSubheading(): ?string
    {
        return 'Nagari '.$this->namaNagari().'.';
    }

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        return AktivitasBelajarData::options($this->nagariId());
    }
}
