<?php

namespace App\Filament\Resources\Wilayahs\Pages;

use App\Filament\Resources\Wilayahs\WilayahResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWilayah extends EditRecord
{
    protected static string $resource = WilayahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
