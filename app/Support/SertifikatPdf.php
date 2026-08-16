<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Pelatihan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Perakit berkas sertifikat.
 *
 * Dipakai dua pihak: pengambilan oleh warga di portal, dan tombol contoh pada form
 * pelatihan di panel. Keduanya WAJIB lewat sini supaya yang dilihat pengajar sebelum
 * menyimpan benar-benar sama dengan yang nanti diterima warga.
 */
class SertifikatPdf
{
    public static function buat(
        Certificate $certificate,
        Pelatihan $pelatihan,
        User $warga,
        bool $contoh = false,
    ): DomPdf {
        return Pdf::loadView('sertifikat.pdf', [
            'certificate' => $certificate,
            'pelatihan' => $pelatihan,
            'warga' => $warga,
            'urlVerifikasi' => $contoh ? '' : $certificate->urlVerifikasi(),
            // Ditanam sebagai data URI, bukan path maupun URL: dompdf berjalan tanpa
            // akses jaringan di produksi, dan chroot-nya berbeda antar lingkungan.
            'logo' => self::logoTertanam(),
            // Contoh tidak membawa QR: nomor serinya tidak ada di basis data, jadi kode
            // yang dipindai hanya akan berujung pada halaman verifikasi yang kosong.
            'qr' => $contoh ? '' : QrSertifikat::dataUri($certificate->urlVerifikasi()),
            'contoh' => $contoh,
        ])->setPaper('a4', 'landscape');
    }

    /** Logo lembaga sebagai data URI; kosong bila berkasnya hilang, agar unduhan tetap jalan. */
    private static function logoTertanam(): string
    {
        $path = public_path('images/brand/basamo-nch-mark.png');

        if (! is_file($path) || ! is_readable($path)) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
