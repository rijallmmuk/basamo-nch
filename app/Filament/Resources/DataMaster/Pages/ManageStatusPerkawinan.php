<?php

namespace App\Filament\Resources\DataMaster\Pages;

use App\Filament\Resources\DataMaster\StatusPerkawinanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusPerkawinan extends ManageRecords
{
    protected static string $resource = StatusPerkawinanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
