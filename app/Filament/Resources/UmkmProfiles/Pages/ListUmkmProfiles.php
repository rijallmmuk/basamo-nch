<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUmkmProfiles extends ListRecords
{
    protected static string $resource = UmkmProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
