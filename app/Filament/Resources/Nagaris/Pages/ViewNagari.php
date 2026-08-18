<?php

namespace App\Filament\Resources\Nagaris\Pages;

use App\Filament\Resources\Nagaris\NagariResource;
use App\Filament\Resources\Nagaris\Support\NagariActions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use App\Support\Reports\NagariProfileExporter;

/**
 * Ringkasan nagari + pusat semua aksi. Akses: superadmin (penuh) & dpmd (read-only).
 * Header: navigasi "Kelola/Lihat" sub-domain + menu "Aksi Nagari" (lifecycle, superadmin;
 * otomatis tersembunyi dari DPMD karena tiap aksinya ter-gate). Definisi aksi dibagi
 * dengan dropdown baris index lewat {@see NagariActions}.
 */
class ViewNagari extends ViewRecord
{
    protected static string $resource = NagariResource::class;

    public function getTitle(): string
    {
        return $this->record->nama_lengkap;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('eksporProfilPdf')
                ->label('Unduh Profil PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => app(NagariProfileExporter::class)->download($this->record, auth()->user())),
            ...NagariActions::navigation(),

            ActionGroup::make(NagariActions::lifecycle())
                ->label('Aksi Nagari')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }
}
