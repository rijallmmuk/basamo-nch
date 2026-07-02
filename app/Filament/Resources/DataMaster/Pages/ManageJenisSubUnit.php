<?php

namespace App\Filament\Resources\DataMaster\Pages;

use App\Filament\Resources\DataMaster\JenisSubUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisSubUnit extends ManageRecords
{
    protected static string $resource = JenisSubUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
