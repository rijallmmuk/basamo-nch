<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Users\UserResource;
use App\Services\WargaProvisioningService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = UserResource::class;

    /**
     * Muat identitas kependudukan ke form (disimpan terpisah di `penduduk`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, app(WargaProvisioningService::class)->pendudukFormData($this->record));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(WargaProvisioningService::class)->update($record, $data);
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
}
