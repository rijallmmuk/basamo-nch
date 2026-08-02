<?php

namespace App\Services\Ews;

use App\Models\EwsDevice;
use App\Models\EwsReading;
use Illuminate\Support\Facades\DB;

/**
 * Merekam pembacaan sensor EWS ke `ews_readings` dan memangkas yang kedaluwarsa.
 *
 * Perekaman berjalan lewat penjadwal, bukan saat halaman dibuka. Dua alasannya:
 * satu panggilan Blynk memakan sekitar 2,5 detik, terlalu lama untuk ditunggu
 * pengunjung; dan riwayat harus tetap terkumpul rapat walaupun tidak seorang pun
 * membuka halamannya, justru karena banjir bandang datang di jam orang tidur.
 *
 * Efek sampingnya menguntungkan: tiap perekaman ikut menghangatkan cache yang
 * dibaca halaman publik.
 */
class EwsRecorderService
{
    public function __construct(private readonly EwsBlynkService $blynk) {}

    /**
     * Rekam satu perangkat. Null bila Blynk tak dapat dihubungi sama sekali;
     * dalam hal itu TIDAK ada baris yang ditulis, karena baris berisi null semua
     * hanya akan mengotori grafik tanpa menambah keterangan apa pun.
     */
    public function rekam(EwsDevice $device): ?EwsReading
    {
        $pembacaan = $this->blynk->ambilSegar($device);

        if ($pembacaan === null) {
            return null;
        }

        return $device->readings()->create([
            'tinggi_air' => $pembacaan['tinggi_air'],
            'curah_hujan' => $pembacaan['curah_hujan'],
            'ph_air' => $pembacaan['ph_air'],
            'getaran' => $pembacaan['getaran'],
            'status_sungai' => $pembacaan['status_sungai'],
            'terhubung' => $pembacaan['terhubung'],
            'direkam_pada' => $pembacaan['diambil_pada'],
        ]);
    }

    /**
     * Buang riwayat yang lebih tua dari batas retensi.
     *
     * Dihapus per potongan, bukan sekali DELETE besar: pada tabel yang tumbuh tiap
     * lima menit sepanjang tahun, satu DELETE raksasa mengunci tabel cukup lama
     * untuk mengganggu perekaman berikutnya.
     */
    public function pangkas(int $umurHari, int $ukuranPotongan = 1000): int
    {
        $batas = now()->subDays(max(1, $umurHari));
        $terhapus = 0;

        do {
            $baris = DB::table('ews_readings')
                ->where('direkam_pada', '<', $batas)
                ->limit($ukuranPotongan)
                ->delete();

            $terhapus += $baris;
        } while ($baris > 0);

        return $terhapus;
    }
}
