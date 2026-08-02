<?php

namespace App\Enums;

/**
 * Status kemajuan desa menurut IDM (Indeks Desa Membangun) Kemendesa — 5 kelas resmi,
 * dari SKOR_SAAT_INI (indeks 0-1). Backing value = string PERSIS dari API (huruf besar)
 * agar {@see tryFrom} langsung memetakan respons tanpa normalisasi.
 */
enum StatusIdm: string
{
    case SangatTertinggal = 'SANGAT TERTINGGAL';
    case Tertinggal = 'TERTINGGAL';
    case Berkembang = 'BERKEMBANG';
    case Maju = 'MAJU';
    case Mandiri = 'MANDIRI';

    /** Label rapi (Title Case) untuk tampilan. */
    public function label(): string
    {
        return match ($this) {
            self::SangatTertinggal => 'Sangat Tertinggal',
            self::Tertinggal => 'Tertinggal',
            self::Berkembang => 'Berkembang',
            self::Maju => 'Maju',
            self::Mandiri => 'Mandiri',
        };
    }

    /** Warna badge Filament sesuai tingkat kemajuan. */
    public function color(): string
    {
        return match ($this) {
            self::SangatTertinggal, self::Tertinggal => 'danger',
            self::Berkembang => 'warning',
            self::Maju => 'info',
            self::Mandiri => 'success',
        };
    }
}
