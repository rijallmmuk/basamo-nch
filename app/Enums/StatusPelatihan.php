<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * SATU-SATUNYA gerbang akses warga, dan letaknya di PELATIHAN. Tiap pelatihan dimiliki
 * pengajar pembuatnya, jadi membuka pelatihan tidak pernah menyingkap kerja pengajar
 * lain. Modul di dalamnya sengaja TIDAK punya status sendiri supaya tidak ada dua
 * saklar untuk satu keputusan.
 */
enum StatusPelatihan: string implements HasColor, HasLabel
{
    /** Terlihat warga sasaran sebagai "Belum dibuka", isinya belum bisa dimasuki. */
    case Terkunci = 'terkunci';

    /** Warga sasaran dapat mempelajari modul-modulnya. */
    case Terbuka = 'terbuka';

    public function getLabel(): string
    {
        return match ($this) {
            self::Terkunci => 'Terkunci',
            self::Terbuka => 'Terbuka',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Terkunci => 'warning',
            self::Terbuka => 'success',
        };
    }

    /** Isi (modul, materi, evaluasi) boleh diakses warga sasaran. */
    public function dapatDimasuki(): bool
    {
        return $this === self::Terbuka;
    }
}
