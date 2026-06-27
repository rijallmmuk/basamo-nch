<?php

namespace App\Filament\Resources\DesaUnits\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\DesaUnits\DesaUnitResource;
use Filament\Resources\Pages\EditRecord;

class EditDesaUnit extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = DesaUnitResource::class;
}
