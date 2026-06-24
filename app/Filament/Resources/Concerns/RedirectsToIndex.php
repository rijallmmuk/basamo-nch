<?php

namespace App\Filament\Resources\Concerns;

/**
 * Setelah Create/Edit berhasil, arahkan ke halaman index (daftar) — bukan ke Edit.
 * Dipakai bersama oleh halaman Create dan Edit agar perilaku seragam lintas resource.
 * Tombol "Buat & buat lainnya" tak terpengaruh — itu tetap di form.
 */
trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
