<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Support\Dashboard\DemografiData;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DemografiUmurChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?string $chartId = 'demografiUmurChart';

    protected static ?string $heading = 'Piramida Penduduk';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getSubheading(): ?string
    {
        $dasar = 'Kelompok umur dan jenis kelamin penduduk terdata di '.$this->namaNagari().'.';
        $tanpaTanggal = DemografiData::tanpaTanggalLahir($this->nagariId());

        return $tanpaTanggal > 0
            ? $dasar.' '.number_format($tanpaTanggal, 0, ',', '.').' penduduk belum punya tanggal lahir dan belum masuk grafik.'
            : $dasar;
    }

    protected function getOptions(): array
    {
        return DemografiData::piramidaUmur($this->nagariId());
    }

    /**
     * Sisi laki-laki digambar dengan angka negatif agar batangnya menjulur ke kiri.
     * Tanpa formatter ini, sumbu dan tooltip ikut menampilkan jumlah orang bernilai
     * minus.
     */
    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            xaxis: { labels: { formatter: function (val) { return Math.abs(Math.round(val)); } } },
            tooltip: { y: { formatter: function (val) { return Math.abs(val) + ' orang'; } } },
        }
        JS);
    }
}
