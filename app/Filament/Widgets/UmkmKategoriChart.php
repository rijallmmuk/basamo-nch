<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\UmkmKategoriData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class UmkmKategoriChart extends ApexChartWidget
{
    use ScopedToNagari;

    protected static ?string $chartId = 'umkmKategoriChart';

    protected static ?string $heading = 'Sebaran Kategori UMKM';

    protected static ?int $sort = 5;

    protected function getSubheading(): ?string
    {
        return 'Nagari '.$this->namaNagari().'.';
    }

    protected function getOptions(): array
    {
        return UmkmKategoriData::options($this->nagariId());
    }
}
