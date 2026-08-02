<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\Support\UmkmProfileActions;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Detail profil UMKM. Pintu supervisi DPMD (ditolak Edit oleh Gate::before);
 * operator/superadmin/pemilik tetap mendapat tombol Edit.
 *
 * Aksi lapak memakai definisi yang sama dengan tabel UMKM ({@see UmkmProfileActions})
 * supaya pengelola tidak menemukan pilihan atau kata-kata berbeda di dua layar.
 */
class ViewUmkmProfile extends ViewRecord
{
    protected static string $resource = UmkmProfileResource::class;

    public function getTitle(): string
    {
        return UmkmProfileResource::isSelfService()
            ? 'Usaha Saya'
            : $this->record->nama_usaha;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Edit Profil UMKM')->color('warning'),

            // Hasil akhir yang dilihat pembeli. Memakai rute cadangan `/n/{nagari}`
            // yang selalu bekerja, termasuk saat subdomain wildcard belum disiapkan.
            Action::make('lihatPublik')
                ->label('Lihat Halaman Publik')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->openUrlInNewTab()
                ->visible(fn (UmkmProfile $record): bool => ! $record->trashed()
                    && $record->status === ActiveStatus::Active
                    && $record->nagari !== null)
                ->url(fn (UmkmProfile $record): string => route(
                    'public.nagari.umkm.etalase.fallback',
                    [$record->nagari, $record],
                )),
            UmkmProfileActions::toggleStatus(),
            ActionGroup::make([
                UmkmProfileActions::archive(),
                UmkmProfileActions::restore(),
                UmkmProfileActions::forceDelete(),
            ])
                ->label('Aksi UMKM')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }
}
