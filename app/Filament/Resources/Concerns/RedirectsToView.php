<?php

namespace App\Filament\Resources\Concerns;

/**
 * Setelah Create/Edit berhasil, arahkan ke halaman view (detail) — bukan ke Index atau Edit.
 * Dipakai bersama oleh halaman Create dan Edit agar perilaku seragam lintas resource kompleks.
 */
trait RedirectsToView
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
