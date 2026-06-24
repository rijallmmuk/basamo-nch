<?php

namespace App\Filament\Resources\Desas\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Desas\DesaResource;
use App\Models\Desa;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDesa extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = DesaResource::class;

    /**
     * Isi field admin (`admin_*`, tak dehidrasi) dari akun admin yang ada.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $admin = $this->record->desaAdmin()->first();

        $data['admin_name'] = $admin?->name;
        $data['admin_username'] = $admin?->username;
        $data['admin_kontak'] = $admin?->phone;
        $data['admin_otp'] = null;

        return $data;
    }

    protected function afterSave(): void
    {
        $otp = DesaResource::syncAdmin($this->record, $this->data);

        if (filled($otp)) {
            Notification::make()
                ->title('OTP admin diperbarui')
                ->body("Username: {$this->record->desaAdmin()->first()->username} · OTP: {$otp}. Sampaikan ke admin desa.")
                ->success()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(fn (Desa $record, DeleteAction $action) => DesaResource::guardAgainstDependents($record, $action))
                ->after(fn (Desa $record) => DesaResource::archiveAdmin($record)),
            ForceDeleteAction::make()
                ->before(fn (Desa $record, ForceDeleteAction $action) => DesaResource::guardAgainstDependents($record, $action, includeTrashed: true))
                ->after(fn (Desa $record) => DesaResource::forceDeleteAdmin($record)),
            RestoreAction::make()
                ->after(fn (Desa $record) => DesaResource::restoreAdmin($record)),
        ];
    }
}
