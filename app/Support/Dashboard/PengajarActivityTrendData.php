<?php

namespace App\Support\Dashboard;

use App\Enums\JenisEvaluasi;
use App\Models\Discussion;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PengajarActivityTrendData
{
    /** Panjang jendela tren: satu bulan (keputusan user 2026-07-31). */
    public const MINGGU = 4;

    /** @return array<string, mixed> */
    public static function options(User $user): array
    {
        $moduleIds = Module::query()->manageableBy($user)->pluck('modules.id');
        $kegiatanIds = Evaluasi::query()
            ->withTrashed()
            ->where('jenis', JenisEvaluasi::Kegiatan)
            ->whereIn('module_id', $moduleIds)
            ->pluck('id');
        $start = now()->startOfWeek()->subWeeks(self::MINGGU - 1);

        // Pengelompokan per minggu dikerjakan basis data. Menarik seluruh stempel
        // waktu ke PHP hanya untuk menghitungnya membuat dasbor ikut berat begitu
        // aktivitas warga menumpuk.
        $started = self::mingguan(
            UserModuleProgress::query()->whereIn('module_id', $moduleIds),
            'created_at',
            $start,
        );
        $completed = self::mingguan(
            UserModuleProgress::query()->whereIn('module_id', $moduleIds),
            'completed_at',
            $start,
        );
        $evaluations = self::mingguan(
            EvaluasiPercobaan::query()->whereIn('evaluasi_id', $kegiatanIds),
            'submitted_at',
            $start,
        );
        $topics = self::mingguan(
            Discussion::query()->whereIn('module_id', $moduleIds)->whereNull('parent_id'),
            'created_at',
            $start,
        );

        $weeks = collect(range(0, self::MINGGU - 1))->map(fn (int $offset): Carbon => $start->copy()->addWeeks($offset));

        return [
            'chart' => [
                'type' => 'area',
                'height' => 360,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'colors' => ['#0284c7', '#10b981', '#7c3aed', '#f59e0b'],
            'series' => [
                ['name' => 'Mulai Belajar', 'data' => self::deret($weeks, $started)],
                ['name' => 'Modul Selesai', 'data' => self::deret($weeks, $completed)],
                ['name' => 'Evaluasi Dikerjakan', 'data' => self::deret($weeks, $evaluations)],
                ['name' => 'Topik Forum', 'data' => self::deret($weeks, $topics)],
            ],
            'xaxis' => [
                'categories' => $weeks->map(fn (Carbon $week): string => $week->translatedFormat('d M'))->all(),
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'min' => 0,
                'forceNiceScale' => true,
                'decimalsInFloat' => 0,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => [
                'type' => 'gradient',
                'gradient' => ['opacityFrom' => 0.28, 'opacityTo' => 0.04],
            ],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'top', 'horizontalAlign' => 'center', 'fontSize' => '12px'],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }

    /**
     * Jumlah baris per awal minggu, dihitung basis data.
     *
     * @return Collection<string, int>
     */
    private static function mingguan(EloquentBuilder $query, string $kolom, Carbon $start): Collection
    {
        // Senin sebagai awal minggu: MariaDB WEEKDAY() memberi 0 untuk Senin, jadi
        // menguranginya dari tanggalnya menjatuhkan tiap baris ke awal minggunya.
        $awalMinggu = "DATE_SUB(DATE({$kolom}), INTERVAL WEEKDAY({$kolom}) DAY)";

        return $query
            ->whereNotNull($kolom)
            ->where($kolom, '>=', $start)
            ->groupBy(DB::raw($awalMinggu))
            ->pluck(DB::raw('COUNT(*) as total'), DB::raw("{$awalMinggu} as minggu"))
            ->mapWithKeys(fn ($total, $minggu) => [Carbon::parse($minggu)->format('Y-m-d') => (int) $total]);
    }

    /**
     * @param  Collection<int, Carbon>  $weeks
     * @param  Collection<string, int>  $counts
     * @return list<int>
     */
    private static function deret(Collection $weeks, Collection $counts): array
    {
        return $weeks
            ->map(fn (Carbon $week): int => (int) $counts->get($week->format('Y-m-d'), 0))
            ->all();
    }
}
