<?php

namespace App\Filament\Resources\DesaUnits\Pages;

use App\Filament\Resources\DesaUnits\DesaUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDesaUnit extends EditRecord
{
    protected static string $resource = DesaUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
