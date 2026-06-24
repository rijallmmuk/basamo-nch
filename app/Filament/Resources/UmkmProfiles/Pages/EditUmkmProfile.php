<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUmkmProfile extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = UmkmProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
