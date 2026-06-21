<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ModuleProgressStatus: string implements HasColor, HasLabel
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotStarted => 'Belum Dimulai',
            self::InProgress => 'Sedang Dipelajari',
            self::Completed => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::InProgress => 'info',
            self::Completed => 'success',
        };
    }
}
