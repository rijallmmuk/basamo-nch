<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\StatusIdm;
use App\Models\IdmStatus;
use App\Models\Nagari;

/**
 * Bacaan lintas nagari untuk dasbor superadmin dan DPMD.
 *
 * Isinya HANYA yang menyandingkan nagari satu per satu. Rata-rata lintas nagari
 * sengaja tidak ada (keputusan user 2026-07-31): angka seperti "capaian SDGs
 * rata-rata seluruh nagari" mengaburkan nagari yang tertinggal dan tidak bisa
 * ditindaklanjuti. Analisis per nagari memakai pemilih nagari di atas dasbor.
 */
class PlatformIdmData
{
    /**
     * Banyaknya nagari pada tiap status IDM, plus yang datanya belum pernah ditarik.
     *
     * @return array<string, mixed>
     */
    public static function sebaranIdm(): array
    {
        // Satu status per nagari: yang dipakai adalah tahun TERBARU nagari itu,
        // bukan seluruh riwayatnya, supaya nagari yang datanya beberapa tahun tidak
        // terhitung berkali-kali.
        $terbaru = IdmStatus::query()
            ->selectRaw('nagari_id, MAX(tahun) as tahun')
            ->groupBy('nagari_id');

        $hitung = IdmStatus::query()
            ->joinSub($terbaru, 'terbaru', fn ($join) => $join
                ->on('idm_statuses.nagari_id', '=', 'terbaru.nagari_id')
                ->on('idm_statuses.tahun', '=', 'terbaru.tahun'))
            ->join('nagaris', 'nagaris.id', '=', 'idm_statuses.nagari_id')
            ->where('nagaris.status', ActiveStatus::Active->value)
            ->selectRaw('idm_statuses.status, COUNT(*) as jumlah')
            ->groupBy('idm_statuses.status')
            ->pluck('jumlah', 'status');

        $nagariAktif = Nagari::where('status', ActiveStatus::Active)->count();
        $belumAdaData = max(0, $nagariAktif - (int) $hitung->sum());

        $label = [];
        $nilai = [];
        $warna = [];

        // Urutan tetap dari terendah ke tertinggi, termasuk status yang kosong,
        // supaya bentuk sebarannya terbaca dan tidak berubah tiap penyegaran.
        foreach (StatusIdm::cases() as $status) {
            $label[] = $status->label();
            $nilai[] = (int) ($hitung[$status->value] ?? 0);
            $warna[] = match ($status) {
                StatusIdm::SangatTertinggal => '#b91c1c',
                StatusIdm::Tertinggal => '#dc2626',
                StatusIdm::Berkembang => '#f59e0b',
                StatusIdm::Maju => '#0284c7',
                StatusIdm::Mandiri => '#059669',
            };
        }

        if ($belumAdaData > 0) {
            $label[] = 'Belum ada data';
            $nilai[] = $belumAdaData;
            $warna[] = '#94a3b8';
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%', 'distributed' => true]],
            'series' => [['name' => 'Nagari', 'data' => $nilai]],
            'xaxis' => [
                'categories' => $label,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'decimalsInFloat' => 0,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'colors' => $warna,
            'dataLabels' => ['enabled' => true],
            'legend' => ['show' => false],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }
}
