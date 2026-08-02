<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\WelcomeData;
use Filament\Widgets\Widget;

/**
 * Kartu sambutan ringkas & profesional di dasbor (pengganti AccountWidget bawaan
 * yang memuat tombol Sign out redundan — keluar sudah tersedia di menu avatar).
 */
class WelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.welcome';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return WelcomeData::forUser(auth()->user());
    }
}
