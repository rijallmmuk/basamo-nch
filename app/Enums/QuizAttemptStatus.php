<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuizAttemptStatus: string implements HasColor, HasLabel
{
    // Auto-grade sinkron: attempt hanya pernah berstatus final (lulus/gagal).
    case Passed = 'passed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Passed => 'Lulus',
            self::Failed => 'Gagal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Passed => 'success',
            self::Failed => 'danger',
        };
    }
}
