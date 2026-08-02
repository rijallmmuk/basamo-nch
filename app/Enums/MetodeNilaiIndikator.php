<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Jenis pengukuran sebuah indikator SDGs (metadata referensi/panduan — sejak
 * penilaian per-poin 2026-07-09 TIDAK dipakai menghitung skor).
 */
enum MetodeNilaiIndikator: string implements HasLabel
{
    case PersenNaik = 'persen_naik';       // persentase, makin tinggi makin baik
    case PersenTurun = 'persen_turun';     // persentase, makin rendah makin baik
    case Boolean = 'boolean';              // ketersediaan Ya/Tidak
    case CapaianTarget = 'capaian_target'; // angka/porsi tanpa jangkar 100

    public function getLabel(): string
    {
        return match ($this) {
            self::PersenNaik => 'Persentase (naik, target 100%)',
            self::PersenTurun => 'Persentase (turun, target 0%)',
            self::Boolean => 'Ketersediaan (Ya/Tidak)',
            self::CapaianTarget => 'Capaian target (dihitung nagari)',
        };
    }
}
