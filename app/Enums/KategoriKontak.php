<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Kategori pesan masuk dari section "Hubungi Kami" beranda publik. Hanya 2
 * (keputusan user 2026-07-13): Keluhan & Saran DILEBUR jadi satu kategori,
 * tidak dipisah lagi.
 */
enum KategoriKontak: string implements HasColor, HasLabel
{
    case Mitra = 'mitra';
    case KeluhanSaran = 'keluhan_saran';
    case LaporanError = 'laporan_error';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mitra => 'Jadi Mitra',
            self::KeluhanSaran => 'Keluhan & Saran',
            self::LaporanError => 'Laporan Error',
            self::Lainnya => 'Lainnya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Mitra => 'success',
            self::KeluhanSaran => 'info',
            self::LaporanError => 'danger',
            self::Lainnya => 'gray',
        };
    }
}
