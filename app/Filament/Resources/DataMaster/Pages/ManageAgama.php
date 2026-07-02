<?php

namespace App\Filament\Resources\DataMaster\Pages;

use App\Filament\Resources\DataMaster\AgamaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAgama extends ManageRecords
{
    protected static string $resource = AgamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
