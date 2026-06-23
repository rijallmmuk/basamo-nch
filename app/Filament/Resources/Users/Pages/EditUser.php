<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\InteractsWithPenduduk;
use App\Filament\Resources\Users\UserResource;
use App\Services\PendudukService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use InteractsWithPenduduk;

    protected static string $resource = UserResource::class;

    /**
     * Muat identitas kependudukan ke form (disimpan terpisah di `penduduk`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($penduduk = $this->record->penduduk) {
            foreach (PendudukService::FIELDS as $field) {
                $data[$field] = $field === 'jenis_kelamin'
                    ? $penduduk->jenis_kelamin?->value
                    : $penduduk->{$field};
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Cegah self-lockout: tak bisa menghapus akun sendiri.
                ->visible(fn (): bool => $this->record->getKey() !== auth()->id()),
            ForceDeleteAction::make()
                ->visible(fn (): bool => $this->record->getKey() !== auth()->id()),
            RestoreAction::make(),
        ];
    }

    /**
     * Resource khusus warga → tak ada peran untuk diubah. Cukup pisahkan field
     * identitas agar disimpan ke `penduduk` di afterSave().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractPendudukData($data);
    }

    protected function afterSave(): void
    {
        $this->syncPenduduk();
    }
}
