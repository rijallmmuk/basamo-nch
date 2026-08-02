<?php

namespace App\Filament\Resources\Pelatihans\Pages;

use App\Enums\StatusPelatihan;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Detail pelatihan dan pintu utama supervisi DPMD.
 */
class ViewPelatihan extends ViewRecord
{
    protected static string $resource = PelatihanResource::class;

    public function getTitle(): string
    {
        return $this->record->namaTampil();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah Pelatihan')
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->trashed()),

            ...$this->statusActions(),

            ActionGroup::make([
                DeleteAction::make()
                    ->modalDescription('Menghapus pelatihan ini SEKALIGUS menyampah seluruh isinya: semua modul, materi, evaluasi, dan diskusi di dalamnya. Semua bisa dipulihkan bersama lewat Pulihkan.'),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->modalDescription('Menghapus PERMANEN pelatihan beserta seluruh modul, materi, evaluasi, dan diskusinya. Tindakan ini tidak dapat dibatalkan.'),
            ])
                ->label('Aksi Lainnya')
                ->icon('heroicon-o-squares-2x2')
                ->button()
                ->color('gray'),
        ];
    }

    /** @return list<Action> */
    private function statusActions(): array
    {
        return [
            Action::make('buka')
                ->label('Buka untuk Warga')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->visible(fn (): bool => ! $this->record->trashed()
                    && $this->record->status !== StatusPelatihan::Terbuka)
                ->authorize(fn (): bool => auth()->user()?->can('kelolaStatus', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Warga di seluruh nagari sasaran dapat mengakses modulnya dan akan diberi notifikasi.')
                ->action(fn () => $this->ubahStatus(StatusPelatihan::Terbuka)),

            Action::make('kunci')
                ->label('Kunci')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->trashed()
                    && $this->record->status !== StatusPelatihan::Terkunci)
                ->authorize(fn (): bool => auth()->user()?->can('kelolaStatus', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Warga tetap melihat pelatihan sebagai "Belum dibuka", tetapi tidak dapat mengakses isinya.')
                ->action(fn () => $this->ubahStatus(StatusPelatihan::Terkunci)),
        ];
    }

    private function ubahStatus(StatusPelatihan $status): void
    {
        try {
            PelatihanResource::setStatus($this->record, $status);
        } catch (\DomainException $e) {
            Notification::make()
                ->title('Status tidak dapat diubah')
                ->body($e->getMessage().' Tambahkan sasaran nagari dan minimal satu materi terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Status pelatihan diperbarui: '.$status->getLabel())
            ->success()
            ->send();
    }
}
