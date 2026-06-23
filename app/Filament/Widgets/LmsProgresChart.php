<?php

namespace App\Filament\Widgets;

use App\Enums\ModuleProgressStatus;
use App\Models\UserModuleProgress;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class LmsProgresChart extends ApexChartWidget
{
    protected static ?string $chartId = 'lmsProgresChart';

    protected static ?string $heading = 'Penyelesaian Modul per Desa';

    protected static ?int $sort = 2;

    protected function getOptions(): array
    {
        $user = auth()->user();

        $rows = UserModuleProgress::query()
            ->where('user_module_progress.status', ModuleProgressStatus::Completed)
            ->join('users', 'users.id', '=', 'user_module_progress.user_id')
            ->join('desas', 'desas.id', '=', 'users.desa_id')
            ->when($user?->isDesaAdmin(), fn ($q) => $q->where('users.desa_id', $user->desa_id))
            ->groupBy('desas.id', 'desas.nama')
            ->orderBy('desas.nama')
            ->select('desas.nama', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'nama');

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Modul selesai',
                    'data' => $rows->values()->all() ?: [0],
                ],
            ],
            'xaxis' => [
                'categories' => $rows->keys()->all() ?: ['Belum ada data'],
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'colors' => ['#003857'],
            'plotOptions' => [
                'bar' => ['borderRadius' => 4, 'horizontal' => false],
            ],
            'dataLabels' => ['enabled' => false],
        ];
    }
}
