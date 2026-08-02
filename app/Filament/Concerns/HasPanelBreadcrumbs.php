<?php

namespace App\Filament\Concerns;

/**
 * Menampilkan penanda lokasi pada halaman panel yang tidak mendapat
 * breadcrumb otomatis dari Filament (Page kustom dan ManageRecords).
 */
trait HasPanelBreadcrumbs
{
    /** @return array<string> */
    public function getBreadcrumbs(): array
    {
        return [(string) $this->getTitle()];
    }
}
