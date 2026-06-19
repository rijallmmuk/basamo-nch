<?php

namespace App\Filament\Resources\Wilayahs\Pages;

use App\Filament\Resources\Wilayahs\WilayahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWilayahs extends ListRecords
{
    protected static string $resource = WilayahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
