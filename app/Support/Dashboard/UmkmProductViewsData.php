<?php

namespace App\Support\Dashboard;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Perbandingan kunjungan antar produk: batang seumur hidup berdampingan dengan
 * batang 30 hari terakhir, supaya produk yang dulu ramai tapi kini sepi tidak
 * terbaca sama dengan produk yang sedang naik.
 */
class UmkmProductViewsData
{
    /**
     * @return array<string, mixed>
     */
    public static function options(User $user): array
    {
        $profile = $user->umkmProfile;

        if (! $profile) {
            return self::kosong();
        }

        $topProducts = $profile->products()
            ->orderByDesc('jumlah_dilihat')
            ->take(8)
            ->get(['id', 'nama_produk', 'jumlah_dilihat']);

        if ($topProducts->isEmpty()) {
            return self::kosong();
        }

        $terkini = UmkmKunjunganData::perProduk($topProducts->modelKeys());

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'plotOptions' => [
                'bar' => ['borderRadius' => 4, 'columnWidth' => '60%'],
            ],
            'colors' => ['#003857', '#d4ac0d'],
            'series' => [
                [
                    'name' => 'Total',
                    'data' => $topProducts->map(fn ($p) => (int) $p->jumlah_dilihat)->all(),
                ],
                [
                    'name' => '30 Hari Terakhir',
                    'data' => $topProducts->map(fn ($p) => (int) ($terkini[$p->getKey()] ?? 0))->all(),
                ],
            ],
            'xaxis' => [
                'categories' => $topProducts->map(fn ($p) => Str::limit($p->nama_produk, 20))->all(),
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'decimalsInFloat' => 0,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'legend' => ['position' => 'bottom', 'labels' => ['fontFamily' => 'inherit']],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function kosong(): array
    {
        return [
            'chart' => ['type' => 'bar', 'height' => 300, 'toolbar' => ['show' => false]],
            'series' => [['name' => 'Total', 'data' => [0]]],
            'xaxis' => ['categories' => ['Belum ada produk']],
            'legend' => ['show' => false],
        ];
    }
}
