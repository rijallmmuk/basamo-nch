<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
     * Saat mengedit akun sendiri, jangan biarkan menurunkan peran atau
     * menonaktifkan diri sendiri (cegah kehilangan akses).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->getKey() === auth()->id()) {
            $data['role'] = $this->record->role;
            $data['status'] = 'active';
            $data['nagari_id'] = $this->record->nagari_id;
        }

        // super_admin (global) tidak terikat nagari.
        if (($data['role'] ?? null) === 'super_admin') {
            $data['nagari_id'] = null;
        }

        return $data;
    }
}
