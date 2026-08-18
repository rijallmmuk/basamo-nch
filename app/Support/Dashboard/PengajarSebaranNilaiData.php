<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\JenisEvaluasi;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\User;

/**
 * Sebaran nilai Evaluasi Kegiatan milik pengajar.
 *
 * Rata-rata saja menyembunyikan bentuk sebarannya: nilai 60 bisa berarti seluruh
 * warga paham separuh materi, atau separuh warga paham penuh dan separuhnya tidak
 * paham sama sekali. Keduanya menuntut tindakan berbeda, jadi bentuknya ditampilkan.
 */
class PengajarSebaranNilaiData
{
    /** Batas bawah tiap keranjang; batas atas keranjang terakhir adalah 100. */
    private const KERANJANG = [
        ['label' => '0–59', 'min' => 0, 'max' => 59],
        ['label' => '60–69', 'min' => 60, 'max' => 69],
        ['label' => '70–79', 'min' => 70, 'max' => 79],
        ['label' => '80–89', 'min' => 80, 'max' => 89],
        ['label' => '90–100', 'min' => 90, 'max' => 100],
    ];

    /**
     * @return array<string, mixed>
     */
    public static function options(User $user): array
    {
        $moduleIds = Module::query()->manageableBy($user)->pluck('modules.id')->all();

        $evaluasiIds = $moduleIds === [] ? [] : Evaluasi::query()
            ->ready()
            ->where('jenis', JenisEvaluasi::Kegiatan)
            ->whereIn('module_id', $moduleIds)
            ->pluck('id')
            ->all();

        $jumlah = self::hitung($evaluasiIds);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'plotOptions' => [
                'bar' => ['borderRadius' => 4, 'columnWidth' => '55%', 'distributed' => true],
            ],
            'colors' => ['#dc2626', '#f59e0b', '#d4ac0d', '#0284c7', '#003857'],
            'series' => [
                ['name' => 'Pengerjaan', 'data' => $jumlah],
            ],
            'xaxis' => [
                'categories' => array_column(self::KERANJANG, 'label'),
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'decimalsInFloat' => 0,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'legend' => ['show' => false],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }

    /**
     * Pengelompokan dikerjakan basis data, bukan PHP: menarik seluruh nilai ke
     * memori hanya untuk menghitungnya berarti dasbor ikut berat begitu pengerjaan
     * warga menumpuk.
     *
     * @param  list<int>  $evaluasiIds
     * @return list<int>
     */
    private static function hitung(array $evaluasiIds): array
    {
        if ($evaluasiIds === []) {
            return array_fill(0, count(self::KERANJANG), 0);
        }

        $query = EvaluasiPercobaan::query()
            ->whereIn('evaluasi_id', $evaluasiIds)
            ->whereHas('user', fn ($users) => $users
                ->role('warga')
                ->where('users.status', ActiveStatus::Active)
                ->whereHas('nagari', fn ($nagaris) => $nagaris->where('status', ActiveStatus::Active)))
            ->whereNotNull('nilai');

        foreach (self::KERANJANG as $index => $keranjang) {
            $query->selectRaw(
                "SUM(nilai BETWEEN ? AND ?) as keranjang_{$index}",
                [$keranjang['min'], $keranjang['max']],
            );
        }

        $baris = $query->first();

        return array_map(
            fn (int $index): int => (int) ($baris?->{"keranjang_{$index}"} ?? 0),
            array_keys(self::KERANJANG),
        );
    }
}
