<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\OperatorDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OperatorSdgsChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $chartId = 'operatorSdgsChart';

    protected static ?string $heading = 'Capaian 18 Poin SDGs';

    protected function getSubheading(): ?string
    {
        return 'Nagari '.$this->namaNagari().'.';
    }

    protected function getOptions(): array
    {
        $data = OperatorDashboardData::forNagari($this->nagari());
        $sdgs = $data['sdgs'];

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'colors' => [
                '#E5243B', '#DDA63A', '#4C9F38', '#C5192D', '#FF3A21', '#26BDE2',
                '#FCC30B', '#A21942', '#FD6925', '#DD1367', '#FD9D24', '#BF8B2E',
                '#3F7E44', '#0A97D9', '#56C02B', '#00689D', '#19486A', '#8F205C',
            ],
            'series' => [
                ['name' => 'Capaian SDGs (%)', 'data' => $sdgs['values']],
            ],
            'xaxis' => [
                'categories' => $sdgs['categories'],
                'labels' => ['style' => ['fontSize' => '10px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'max' => 100,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'legend' => ['show' => false],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => [
                'bar' => ['borderRadius' => 4, 'columnWidth' => '50%', 'distributed' => true],
            ],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
        ];
    }
}
