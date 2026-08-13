<?php

namespace App\Support\Dashboard;

use App\Enums\ModuleProgressStatus;
use App\Models\Nagari;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\UserModuleProgress;

/**
 * Ringkasan LINTAS nagari untuk superadmin dan DPMD.
 *
 * Isinya hanya penjumlahan dan pencacahan. Rata-rata lintas nagari DIHAPUS
 * 2026-07-31 atas keputusan user: "capaian SDGs rata-rata seluruh nagari" dan
 * "rata-rata nilai evaluasi seluruh nagari" menyembunyikan nagari yang tertinggal
 * di balik satu angka. Keduanya kini dilihat per nagari lewat pemilih di atas
 * dasbor.
 */
class SuperadminDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public static function overview(): array
    {
        $nagariCount = Nagari::where('status', 'active')->count();
        $kabupatenCount = Nagari::where('status', 'active')->distinct('kabupaten')->count('kabupaten');

        // Penduduk terdata dan akun portal adalah dua populasi berbeda; keduanya
        // disebut terpisah, sama seperti pada dasbor operator.
        $demografi = DemografiData::ringkasan(hanyaNagariAktif: true);
        $pendudukCount = $demografi['penduduk'];
        $wargaCount = $demografi['akun_portal'];

        $modulSelesaiCount = UserModuleProgress::where('status', ModuleProgressStatus::Completed->value)->count();

        $umkmCount = UmkmProfile::count();
        $productCount = UmkmProduct::count();
        // Kunjungan produk dan kunjungan etalase dua angka berbeda; jangan tertukar.
        $productTotalViews = (int) UmkmProduct::sum('jumlah_dilihat');
        $etalaseTotalViews = (int) UmkmProfile::sum('jumlah_dilihat');

        return [
            'metrics' => [
                'nagariCount' => $nagariCount,
                'kabupatenCount' => $kabupatenCount,
                'pendudukCount' => $pendudukCount,
                'wargaCount' => $wargaCount,
                'modulSelesaiCount' => $modulSelesaiCount,
                'umkmCount' => $umkmCount,
                'productCount' => $productCount,
                'productTotalViews' => $productTotalViews,
                'etalaseTotalViews' => $etalaseTotalViews,
            ],
        ];
    }
}
