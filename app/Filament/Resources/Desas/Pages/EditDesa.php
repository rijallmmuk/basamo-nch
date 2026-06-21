<?php

namespace App\Filament\Resources\Desas\Pages;

use App\Filament\Resources\Desas\DesaResource;
use App\Models\Desa;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDesa extends EditRecord
{
    protected static string $resource = DesaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(fn (Desa $record, DeleteAction $action) => DesaResource::guardAgainstDependents($record, $action)),
            ForceDeleteAction::make()
                ->before(fn (Desa $record, ForceDeleteAction $action) => DesaResource::guardAgainstDependents($record, $action, includeTrashed: true)),
            RestoreAction::make(),
        ];
    }
}
