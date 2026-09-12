<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusBerita: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Diterbitkan = 'diterbitkan';
    case Diarsipkan = 'diarsipkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diterbitkan => 'Diterbitkan',
            self::Diarsipkan => 'Diarsipkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Diterbitkan => 'success',
            self::Diarsipkan => 'danger',
        };
    }

    public function isDiterbitkan(): bool
    {
        return $this === self::Diterbitkan;
    }
}
