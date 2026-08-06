<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\PengajarSebaranNilaiData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PengajarSebaranNilaiChart extends ApexChartWidget
{
    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'pengajarSebaranNilaiChart';

    protected static ?string $heading = 'Sebaran Nilai Evaluasi Kegiatan';

    protected static ?string $subheading = 'Banyaknya pengerjaan pada tiap rentang nilai.';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('pengajar');
    }

    protected function getOptions(): array
    {
        $user = auth()->user();

        return $user ? PengajarSebaranNilaiData::options($user) : [];
    }
}
