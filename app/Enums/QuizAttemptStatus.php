<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuizAttemptStatus: string implements HasColor, HasLabel
{
    case InProgress = 'in_progress';
    case Passed = 'passed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang Dikerjakan',
            self::Passed => 'Lulus',
            self::Failed => 'Gagal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InProgress => 'info',
            self::Passed => 'success',
            self::Failed => 'danger',
        };
    }
}
