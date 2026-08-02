<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Tingkat kesiagaan sungai yang dilaporkan alat EWS pada pin V6.
 *
 * Alat mengirim TEKS BEBAS, bukan kode: tiga panel yang ada saja sudah menulis
 * "Aman" dan "AMAN" berbeda huruf, dan tulisan itu ditentukan sketch di perangkat
 * yang bisa diganti teknisi lapangan kapan saja. Karena itu ia dinormalkan di sini,
 * bukan dipercaya apa adanya, dan teks yang tak dikenali TIDAK dianggap aman.
 *
 * Menganggap teks asing sebagai "Aman" adalah kesalahan paling mahal yang bisa
 * dibuat sistem peringatan dini: warga membaca hijau padahal alat sedang berteriak.
 */
enum StatusSungai: string implements HasColor, HasLabel
{
    case Aman = 'aman';
    case Waspada = 'waspada';
    case Siaga = 'siaga';
    case Awas = 'awas';

    /** Alat mengirim sesuatu yang tidak dikenali, atau tidak mengirim apa pun. */
    case TidakDiketahui = 'tidak_diketahui';

    /**
     * Terjemahkan teks mentah dari perangkat. Pencocokan memakai "mengandung",
     * bukan sama persis, karena sketch perangkat kerap menambahi keterangan
     * (mis. "BAHAYA! Air naik"). Diperiksa dari yang paling gawat supaya kalimat
     * yang menyebut dua tingkat sekaligus jatuh ke tingkat tertinggi.
     */
    public static function dariTeks(?string $teks): self
    {
        $bersih = mb_strtolower(trim((string) $teks));

        if ($bersih === '') {
            return self::TidakDiketahui;
        }

        return match (true) {
            str_contains($bersih, 'awas'), str_contains($bersih, 'bahaya') => self::Awas,
            str_contains($bersih, 'siaga') => self::Siaga,
            str_contains($bersih, 'waspada') => self::Waspada,
            str_contains($bersih, 'aman'), str_contains($bersih, 'normal') => self::Aman,
            default => self::TidakDiketahui,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Aman => 'Aman',
            self::Waspada => 'Waspada',
            self::Siaga => 'Siaga',
            self::Awas => 'Awas',
            self::TidakDiketahui => 'Tidak diketahui',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aman => 'success',
            self::Waspada => 'warning',
            self::Siaga => 'warning',
            self::Awas => 'danger',
            self::TidakDiketahui => 'gray',
        };
    }

    /** Kelas warna Tailwind untuk halaman publik (di luar palet Filament). */
    public function kelasWarna(): string
    {
        return match ($this) {
            self::Aman => 'text-emerald-600 dark:text-emerald-400',
            self::Waspada => 'text-amber-600 dark:text-amber-400',
            self::Siaga => 'text-orange-600 dark:text-orange-400',
            self::Awas => 'text-red-600 dark:text-red-400',
            self::TidakDiketahui => 'text-on-surface-variant',
        };
    }

    /** Perlu ditonjolkan sebagai peringatan, bukan sekadar informasi. */
    public function perluPerhatian(): bool
    {
        return in_array($this, [self::Waspada, self::Siaga, self::Awas], true);
    }
}
