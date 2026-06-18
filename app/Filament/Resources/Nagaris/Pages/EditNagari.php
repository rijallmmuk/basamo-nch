<?php

namespace App\Filament\Resources\Nagaris\Pages;

use App\Filament\Resources\Nagaris\NagariResource;
use App\Models\Nagari;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditNagari extends EditRecord
{
    protected static string $resource = NagariResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(fn (Nagari $record, DeleteAction $action) => NagariResource::guardAgainstDependents($record, $action)),
            ForceDeleteAction::make()
                ->before(fn (Nagari $record, ForceDeleteAction $action) => NagariResource::guardAgainstDependents($record, $action, includeTrashed: true)),
            RestoreAction::make(),
        ];
    }
}
