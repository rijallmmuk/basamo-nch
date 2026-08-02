<?php

namespace App\Filament\Resources\BackofficeUsers\Pages;

use App\Filament\Resources\BackofficeUsers\BackofficeUserResource;
use App\Filament\Resources\Concerns\RedirectsToView;
use App\Services\BackofficeUserService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBackofficeUser extends EditRecord
{
    use RedirectsToView;

    protected static string $resource = BackofficeUserResource::class;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Field peran kini pilihan tunggal, jadi isinya string, bukan array.
        $data['role_names'] = $this->record->getRoleNames()
            ->reject(fn (string $role): bool => in_array($role, BackofficeUserService::PORTAL_ROLES, true))
            ->first();

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Service tetap menerima array; nilai tunggal dibungkus di sini.
        $dipilih = array_filter((array) ($data['role_names'] ?? []));

        $roles = $record->hasRole('operator')
            ? array_values(array_unique(['operator', ...$dipilih]))
            : array_values($dipilih);
        unset($data['role_names']);

        return app(BackofficeUserService::class)->update($record, $data, $roles);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
