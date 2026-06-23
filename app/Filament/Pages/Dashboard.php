<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    /**
     * Sembunyikan judul "Dasbor" di atas kartu sapaan — sapaan sudah menjadi
     * pembuka halaman. Judul tab browser tetap (getTitle bawaan).
     */
    public function getHeading(): string|Htmlable|null
    {
        return '';
    }
}
