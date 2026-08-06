<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\OperatorDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OperatorLmsProgressChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?int $sort = 2;

    protected static ?string $chartId = 'operatorLmsProgressChart';

    protected static ?string $heading = 'Penyelesaian Modul oleh Warga';

    protected function getSubheading(): ?string
    {
        return 'Nagari '.$this->namaNagari().'.';
    }

    protected function getOptions(): array
    {
        $data = OperatorDashboardData::forNagari($this->nagari());
        $lms = $data['lmsModules'];

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 280,
                'stacked' => true,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'colors' => ['#10b981', '#0284c7'],
            'series' => [
                ['name' => 'Warga Selesai', 'data' => $lms['completed']],
                ['name' => 'Sedang Belajar', 'data' => $lms['inProgress']],
            ],
            'xaxis' => [
                'categories' => $lms['categories'],
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'legend' => ['position' => 'top', 'horizontalAlign' => 'center', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => [
                'bar' => ['borderRadius' => 4, 'columnWidth' => '40%'],
            ],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
        ];
    }
}
