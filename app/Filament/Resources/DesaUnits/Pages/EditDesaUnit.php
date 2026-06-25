<?php

namespace App\Filament\Resources\DesaUnits\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\DesaUnits\DesaUnitResource;
use App\Models\DesaUnit;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDesaUnit extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = DesaUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(fn (DesaUnit $record, DeleteAction $action) => DesaUnitResource::guardAgainstWarga($record, $action)),
            ForceDeleteAction::make()
                ->before(fn (DesaUnit $record, ForceDeleteAction $action) => DesaUnitResource::guardAgainstWarga($record, $action, includeTrashed: true)),
            RestoreAction::make(),
        ];
    }
}
