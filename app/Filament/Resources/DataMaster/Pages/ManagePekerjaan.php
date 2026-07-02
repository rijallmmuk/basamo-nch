<?php

namespace App\Filament\Resources\DataMaster\Pages;

use App\Filament\Resources\DataMaster\PekerjaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePekerjaan extends ManageRecords
{
    protected static string $resource = PekerjaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
