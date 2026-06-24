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
     * desa_admin tidak melihat field desa — wilayah dipaksa ke desanya.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if ($actor->isDesaAdmin()) {
            $data['desa_id'] = $actor->desa_id;
        }

        return $data;
    }
}
