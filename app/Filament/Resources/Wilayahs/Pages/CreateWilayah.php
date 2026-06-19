<?php

namespace App\Filament\Resources\Wilayahs\Pages;

use App\Filament\Resources\Wilayahs\WilayahResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWilayah extends CreateRecord
{
    protected static string $resource = WilayahResource::class;

    /**
     * nagari_admin tidak melihat field nagari — wilayah dipaksa ke nagarinya.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if ($actor->isNagariAdmin()) {
            $data['nagari_id'] = $actor->nagari_id;
        }

        return $data;
    }
}
