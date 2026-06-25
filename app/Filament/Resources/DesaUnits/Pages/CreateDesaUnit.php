<?php

namespace App\Filament\Resources\DesaUnits\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\DesaUnits\DesaUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDesaUnit extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = DesaUnitResource::class;

    /**
     * Dalam konteks desa (desa_admin → desanya; super admin → desa yang dikelola),
     * desa dipaksa server-side — field pemilih desa disembunyikan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $desaId = auth()->user()?->managedDesaId();

        if ($desaId !== null) {
            $data['desa_id'] = $desaId;
        }

        return $data;
    }
}
