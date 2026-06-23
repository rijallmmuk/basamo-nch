<?php

namespace App\Filament\Widgets;

use App\Enums\ModuleProgressStatus;
use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use App\Models\UserModuleProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AktivitasBelajarChart extends ApexChartWidget
{
    protected static ?string $chartId = 'aktivitasBelajarChart';

    protected static ?string $heading = 'Aktivitas Belajar 30 Hari';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getOptions(): array
    {
        $user = auth()->user();
        $desaId = $user?->isDesaAdmin() ? $user->desa_id : null;

        $since = now()->subDays(29)->startOfDay();

        // Bucket harian: tanggal => 0, untuk 30 hari terakhir.
        $buckets = collect(range(0, 29))
            ->mapWithKeys(fn (int $i) => [$since->copy()->addDays($i)->toDateString() => 0]);

        $modul = $this->countByDay(
            UserModuleProgress::query()
                ->where('user_module_progress.status', ModuleProgressStatus::Completed)
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $since)
                ->when($desaId, fn ($q) => $q
                    ->join('users', 'users.id', '=', 'user_module_progress.user_id')
                    ->where('users.desa_id', $desaId)),
            'completed_at',
            $buckets,
        );

        $kuis = $this->countByDay(
            QuizAttempt::query()
                ->where('quiz_attempts.status', QuizAttemptStatus::Passed)
                ->whereNotNull('submitted_at')
                ->where('submitted_at', '>=', $since)
                ->when($desaId, fn ($q) => $q
                    ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
                    ->where('users.desa_id', $desaId)),
            'submitted_at',
            $buckets,
        );

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                ['name' => 'Modul selesai', 'data' => $modul->values()->all()],
                ['name' => 'Kuis lulus', 'data' => $kuis->values()->all()],
            ],
            'xaxis' => [
                'categories' => $buckets->keys()
                    ->map(fn (string $d) => Carbon::parse($d)->format('d/m'))
                    ->all(),
                'labels' => ['style' => ['fontFamily' => 'inherit'], 'rotate' => -45],
                'tickAmount' => 10,
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'colors' => ['#003857', '#d4ac0d'],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.4, 'opacityTo' => 0.1]],
            'dataLabels' => ['enabled' => false],
        ];
    }

    /**
     * Hitung jumlah baris per hari, dipetakan ke bucket 30 hari.
     *
     * @param  Collection<string, int>  $buckets
     * @return Collection<string, int>
     */
    protected function countByDay($query, string $column, $buckets)
    {
        $counts = $query
            ->groupBy(DB::raw("DATE($column)"))
            ->pluck(DB::raw('COUNT(*) as total'), DB::raw("DATE($column) as tanggal"));

        return $buckets->map(fn (int $zero, string $date) => (int) ($counts[$date] ?? 0));
    }
}
