<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status pengajuan akses UMKM mandiri oleh warga (null di kolom = tak ada
 * pengajuan berjalan — belum pernah mengajukan, atau sudah disetujui).
 */
enum PengajuanUmkmStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'menunggu';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Tinjauan',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Ditolak => 'danger',
        };
    }
}
