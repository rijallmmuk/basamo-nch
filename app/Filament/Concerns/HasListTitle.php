<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Judul halaman index seragam "Daftar {Nama Menu}" (mis. "Daftar Nagari",
 * "Daftar Warga") — dipakai di semua halaman List resource.
 */
trait HasListTitle
{
    public function getTitle(): string|Htmlable
    {
        return 'Daftar '.static::getResource()::getPluralModelLabel();
    }
}
