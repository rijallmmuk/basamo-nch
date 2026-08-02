<?php

namespace App\Support;

/**
 * Kunci pembanding keunikan Tema Pelatihan.
 *
 * Sengaja TIDAK agresif. Yang disamakan hanya perbedaan yang tidak membawa
 * makna: besar-kecil huruf, spasi berlebih, jenis spasi/tanda hubung/kutip yang
 * berbeda-beda tergantung papan ketik. Simbol yang MEMBAWA makna dipertahankan,
 * sehingga:
 *
 *   "Digital Marketing UMKM" == "digital  marketing umkm" == "Digital-Marketing UMKM"
 *   "C++" != "C"            "UI/UX" != "UIUX"            "Excel 2016" != "Excel"
 *
 * Kata "pelatihan" dan angka TIDAK dibuang: keduanya bisa jadi bagian sah dari
 * nama tema. Nama yang mirip tetapi tidak identik diserahkan ke
 * TemaPelatihan::miripDengan() sebagai SARAN, bukan digabung paksa.
 */
final class TemaNormalizer
{
    /** Spasi non-standar (nbsp, en/em space, dst) yang harus jadi spasi biasa. */
    private const SPASI = '/[\x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u';

    /** Varian tanda hubung yang hanya jadi pemisah kata, disamakan dengan spasi. */
    private const HUBUNG = '/[\x{2010}-\x{2015}\x{2212}\-]+/u';

    /** Varian kutip tunggal & ganda dari papan ketik/word processor. */
    private const KUTIP_TUNGGAL = '/[\x{2018}\x{2019}\x{02BC}\x{0060}\x{00B4}]/u';

    private const KUTIP_GANDA = '/[\x{201C}\x{201D}]/u';

    public static function normalize(string $nama): string
    {
        $hasil = preg_replace(self::SPASI, ' ', $nama) ?? $nama;
        $hasil = preg_replace(self::KUTIP_TUNGGAL, "'", $hasil) ?? $hasil;
        $hasil = preg_replace(self::KUTIP_GANDA, '"', $hasil) ?? $hasil;
        $hasil = preg_replace(self::HUBUNG, ' ', $hasil) ?? $hasil;
        $hasil = mb_strtolower($hasil, 'UTF-8');
        $hasil = preg_replace('/\s+/u', ' ', $hasil) ?? $hasil;

        return trim($hasil);
    }

    /**
     * Bentuk paling telanjang, HANYA untuk mencari nama mirip (saran), tidak
     * pernah dipakai sebagai kunci keunikan. Di sini simbol memang dibuang
     * supaya "UI/UX" dan "UI UX" bisa saling disarankan tanpa digabung otomatis.
     */
    public static function fingerprint(string $nama): string
    {
        $hasil = self::normalize($nama);
        $hasil = preg_replace('/[^\p{L}\p{N}]+/u', '', $hasil) ?? $hasil;

        return $hasil;
    }
}
