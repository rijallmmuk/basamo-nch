<?php

namespace App\Filament\Resources\DesaUnits\Pages;

use App\Filament\Resources\DesaUnits\DesaUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesaUnits extends ListRecords
{
    protected static string $resource = DesaUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
