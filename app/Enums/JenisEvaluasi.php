<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Dua jenis evaluasi milik sebuah modul. Satu modul boleh punya paling banyak
 * satu evaluasi AKTIF per jenis (unique gabungan active_module_id + jenis).
 */
enum JenisEvaluasi: string implements HasColor, HasLabel
{
    /** Gerbang opsional sebelum materi terbuka. Tanpa nilai lulus, sekali percobaan. */
    case Pretest = 'pretest';

    /** Penutup modul (dulu bernama "kuis"). */
    case Kegiatan = 'kegiatan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pretest => 'Pre-test',
            self::Kegiatan => 'Evaluasi Kegiatan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pretest => 'info',
            self::Kegiatan => 'primary',
        };
    }

    /** Pre-test tidak mengenal lulus/gagal, hanya sudah atau belum dikerjakan. */
    public function memakaiNilaiLulus(): bool
    {
        return $this === self::Kegiatan;
    }

    /** 0 = tak dibatasi. Pre-test dikunci satu percobaan. */
    public function maksPercobaanTetap(): ?int
    {
        return $this === self::Pretest ? 1 : null;
    }
}
