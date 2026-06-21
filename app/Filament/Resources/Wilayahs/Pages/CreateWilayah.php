<?php

namespace App\Filament\Resources\Wilayahs\Pages;

use App\Filament\Resources\Wilayahs\WilayahResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWilayah extends CreateRecord
{
    protected static string $resource = WilayahResource::class;

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
