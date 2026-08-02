<?php

namespace App\Support\Dashboard;

use App\Models\User;

/**
 * Opsi grafik garis tren kunjungan harian lapak dan produk milik satu pemilik.
 */
class UmkmTrenKunjunganData
{
    /**
     * @return array<string, mixed>
     */
    public static function options(User $user): array
    {
        $profile = $user->umkmProfile;

        if (! $profile) {
            return self::kosong(['Belum ada lapak']);
        }

        $tren = UmkmKunjunganData::tren($profile);

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
                'zoom' => ['enabled' => false],
            ],
            'series' => [
                ['name' => 'Etalase Usaha', 'data' => $tren['lapak']],
                ['name' => 'Detail Produk', 'data' => $tren['produk']],
            ],
            'xaxis' => [
                'categories' => $tren['tanggal'],
                'tickAmount' => 10,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                // Kunjungan selalu bilangan bulat; tanpa ini sumbu menampilkan 0,5.
                'decimalsInFloat' => 0,
                'forceNiceScale' => true,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'colors' => ['#003857', '#d4ac0d'],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => [
                'type' => 'gradient',
                'gradient' => ['shadeIntensity' => 0.2, 'opacityFrom' => 0.35, 'opacityTo' => 0.05],
            ],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'bottom', 'labels' => ['fontFamily' => 'inherit']],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }

    /**
     * @param  list<string>  $kategori
     * @return array<string, mixed>
     */
    private static function kosong(array $kategori): array
    {
        return [
            'chart' => ['type' => 'area', 'height' => 300, 'toolbar' => ['show' => false]],
            'series' => [['name' => 'Kunjungan', 'data' => [0]]],
            'xaxis' => ['categories' => $kategori],
        ];
    }
}
