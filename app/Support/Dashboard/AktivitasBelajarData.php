<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Models\EvaluasiPercobaan;
use App\Models\UserModuleProgress;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Aktivitas belajar 30 hari (area chart) — $nagariId null = lintas-nagari (superadmin). */
class AktivitasBelajarData
{
    /** @return array<string, mixed> opsi ApexCharts (area) */
    public static function options(?int $nagariId): array
    {
        $since = now()->subDays(29)->startOfDay();

        // Bucket harian: tanggal => 0, untuk 30 hari terakhir.
        $buckets = collect(range(0, 29))
            ->mapWithKeys(fn (int $i) => [$since->copy()->addDays($i)->toDateString() => 0]);

        $modul = self::countByDay(
            UserModuleProgress::query()
                ->where('user_module_progress.status', ModuleProgressStatus::Completed)
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $since)
                ->whereHas('user', fn ($users) => $users
                    ->role('warga')
                    ->where('users.status', ActiveStatus::Active)
                    ->whereHas('nagari', fn ($nagaris) => $nagaris->where('status', ActiveStatus::Active))
                    ->when($nagariId, fn ($scope) => $scope->where('users.nagari_id', $nagariId))),
            'completed_at',
            $buckets,
        );

        $kuis = self::countByDay(
            EvaluasiPercobaan::query()
                ->where('evaluasi_percobaans.status', StatusPercobaan::Passed)
                ->whereNotNull('submitted_at')
                ->where('submitted_at', '>=', $since)
                ->whereHas('user', fn ($users) => $users
                    ->role('warga')
                    ->where('users.status', ActiveStatus::Active)
                    ->whereHas('nagari', fn ($nagaris) => $nagaris->where('status', ActiveStatus::Active))
                    ->when($nagariId, fn ($scope) => $scope->where('users.nagari_id', $nagariId))),
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
                ['name' => 'Evaluasi lulus', 'data' => $kuis->values()->all()],
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
    private static function countByDay(Builder $query, string $column, Collection $buckets): Collection
    {
        $counts = $query
            ->groupBy(DB::raw("DATE($column)"))
            ->pluck(DB::raw('COUNT(*) as total'), DB::raw("DATE($column) as tanggal"));

        return $buckets->map(fn (int $zero, string $date) => (int) ($counts[$date] ?? 0));
    }
}
