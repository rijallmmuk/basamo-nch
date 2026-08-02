<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\User;

class UmkmOverviewData
{
    /**
     * @return list<array{label: string, value: string|int, description: string, icon: string, color: string, url?: string}>
     */
    public static function stats(User $user): array
    {
        $profile = $user->umkmProfile;

        if (! $profile) {
            return [
                [
                    'label' => 'Status Lapak',
                    'value' => 'Belum Ada Lapak',
                    // Kartu ini adalah satu-satunya hal di dasbor pemilik baru, jadi ia
                    // harus mengantar ke tindakan yang dimintanya, bukan sekadar
                    // menyuruh mencari sendiri di sidebar.
                    'description' => 'Klik untuk membuat profil UMKM Anda',
                    'icon' => 'heroicon-m-exclamation-circle',
                    'color' => 'warning',
                    'url' => UmkmProfileResource::getUrl('index'),
                ],
            ];
        }

        $totalProduk = $profile->products()->count();
        $lapakAktif = $profile->status === ActiveStatus::Active;
        $kunjunganLapak = (int) $profile->jumlah_dilihat;
        $totalViewsProduk = (int) $profile->products()->sum('jumlah_dilihat');

        $lapakTerkini = UmkmKunjunganData::kunjunganLapak($profile);
        $produkTerkini = UmkmKunjunganData::kunjunganProduk($profile);

        return [
            [
                'label' => 'Total Produk',
                'value' => $totalProduk,
                'description' => $lapakAktif
                    ? 'Semuanya tampil di etalase publik'
                    : 'Belum tampil karena lapak sedang nonaktif',
                'icon' => 'heroicon-m-shopping-bag',
                'color' => 'primary',
            ],
            [
                'label' => 'Kunjungan Lapak Usaha',
                'value' => self::angka($kunjunganLapak).'×',
                'description' => self::keterangan($lapakTerkini),
                'icon' => 'heroicon-m-building-storefront',
                'color' => 'info',
            ],
            [
                'label' => 'Total Tayangan Produk',
                'value' => self::angka($totalViewsProduk).'×',
                'description' => self::keterangan($produkTerkini),
                'icon' => 'heroicon-m-eye',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Angka besar pada kartu adalah TOTAL seumur hidup, sedangkan rekap harian baru
     * berjalan sejak fitur tren dipasang. Keduanya karena itu tidak boleh dikurangkan
     * atau disebut sebagai bagian satu sama lain; rentang 30 hari cukup disebut apa
     * adanya sebagai keterangan.
     */
    private static function keterangan(int $terkini): string
    {
        return $terkini > 0
            ? self::angka($terkini).'× dalam 30 hari terakhir'
            : 'Belum ada kunjungan dalam 30 hari terakhir';
    }

    private static function angka(int $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }
}
