<?php

namespace App\Filament\Resources\DataMaster\Pages;

use App\Filament\Resources\DataMaster\JenisDesaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisDesa extends ManageRecords
{
    protected static string $resource = JenisDesaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
