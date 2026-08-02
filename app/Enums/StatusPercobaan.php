<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status satu percobaan pengerjaan evaluasi. Auto-grade sinkron: percobaan
 * hanya pernah berstatus final.
 *
 * Pre-test memakai `Selesai` karena tidak mengenal syarat nilai minimum —
 * nilainya tetap dicatat sebagai data awal pembanding, bukan penentu lulus.
 */
enum StatusPercobaan: string implements HasColor, HasLabel
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Selesai = 'selesai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Passed => 'Lulus',
            self::Failed => 'Gagal',
            self::Selesai => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Passed => 'success',
            self::Failed => 'danger',
            self::Selesai => 'info',
        };
    }
}
