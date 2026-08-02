<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Models\UmkmProduct;

/** Sebaran kategori UMKM (donut) — $nagariId null = lintas-nagari (superadmin). */
class UmkmKategoriData
{
    /** @return array<string, mixed> opsi ApexCharts (donut) */
    public static function options(?int $nagariId): array
    {
        // Potret KATALOG: lapak aktif di nagari aktif (produk tak punya status terbit).
        $data = UmkmProduct::query()
            ->join('umkm_profiles', 'umkm_profiles.id', '=', 'umkm_products.umkm_profile_id')
            ->join('nagaris', 'nagaris.id', '=', 'umkm_profiles.nagari_id')
            ->where('umkm_profiles.status', ActiveStatus::Active)
            ->whereNull('umkm_profiles.deleted_at')
            ->where('nagaris.status', ActiveStatus::Active)
            ->whereNull('nagaris.deleted_at')
            ->when($nagariId, fn ($q) => $q->where('umkm_profiles.nagari_id', $nagariId))
            ->join('umkm_categories', 'umkm_categories.id', '=', 'umkm_products.umkm_category_id')
            ->selectRaw('umkm_categories.nama as kategori, COUNT(*) as total')
            ->groupBy('umkm_categories.nama')
            ->orderByDesc('total')
            ->pluck('total', 'kategori');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $data->values()->all() ?: [1],
            'labels' => $data->keys()->all() ?: ['Belum ada data'],
            'legend' => [
                'position' => 'bottom',
                'labels' => ['fontFamily' => 'inherit'],
            ],
            // Palet kategori bernuansa NCH (tetap kontras antar-kategori).
            'colors' => ['#003857', '#d4ac0d', '#00572a', '#1b4f72', '#9dcbf4', '#735c00', '#54d280'],
        ];
    }
}
