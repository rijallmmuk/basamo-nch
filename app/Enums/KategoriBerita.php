<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum KategoriBerita: string implements HasColor, HasIcon, HasLabel
{
    case Berita = 'berita';
    case Pengumuman = 'pengumuman';
    case Agenda = 'agenda';

    public function getLabel(): string
    {
        return match ($this) {
            self::Berita => 'Berita',
            self::Pengumuman => 'Pengumuman',
            self::Agenda => 'Agenda Kegiatan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Berita => 'info',
            self::Pengumuman => 'warning',
            self::Agenda => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Berita => 'heroicon-o-newspaper',
            self::Pengumuman => 'heroicon-o-megaphone',
            self::Agenda => 'heroicon-o-calendar',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Berita => 'bg-blue-600 text-white',
            self::Pengumuman => 'bg-amber-600 text-white',
            self::Agenda => 'bg-purple-600 text-white',
        };
    }
}
