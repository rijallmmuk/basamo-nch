<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ModulePageType: string implements HasColor, HasIcon, HasLabel
{
    case Text = 'text';
    case Video = 'video';
    case Pdf = 'pdf';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Teks',
            self::Video => 'Video',
            self::Pdf => 'PDF',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Text => 'info',
            self::Video => 'success',
            self::Pdf => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-document-text',
            self::Video => 'heroicon-o-play-circle',
            self::Pdf => 'heroicon-o-document',
        };
    }
}
