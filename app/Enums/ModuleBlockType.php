<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Tipe blok konten dalam satu halaman materi (model block-based: satu halaman
 * bisa mencampur & mengurut beberapa blok bebas). Disimpan sebagai `type` tiap
 * item pada kolom JSON `materis.blocks`.
 */
enum ModuleBlockType: string implements HasColor, HasIcon, HasLabel
{
    case Teks = 'teks';
    case Video = 'video';
    case Pdf = 'pdf';
    case Gambar = 'gambar';
    case Audio = 'audio';
    case Lampiran = 'lampiran';

    public function getLabel(): string
    {
        return match ($this) {
            self::Teks => 'Teks',
            self::Video => 'Video',
            self::Pdf => 'PDF',
            self::Gambar => 'Gambar',
            self::Audio => 'Audio',
            self::Lampiran => 'Lampiran',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Teks => 'info',
            self::Video => 'success',
            self::Pdf => 'danger',
            self::Gambar => 'warning',
            self::Audio => 'primary',
            self::Lampiran => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Teks => 'heroicon-o-document-text',
            self::Video => 'heroicon-o-play-circle',
            self::Pdf => 'heroicon-o-document',
            self::Gambar => 'heroicon-o-photo',
            self::Audio => 'heroicon-o-musical-note',
            self::Lampiran => 'heroicon-o-paper-clip',
        };
    }

    /** Tipe blok yang menyimpan satu berkas terunggah di `data.file`. */
    public function storesFile(): bool
    {
        return match ($this) {
            self::Pdf, self::Gambar, self::Audio, self::Lampiran => true,
            default => false,
        };
    }
}
