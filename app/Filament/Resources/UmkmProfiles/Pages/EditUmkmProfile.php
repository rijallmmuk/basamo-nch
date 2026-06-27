<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use Filament\Resources\Pages\EditRecord;

class EditUmkmProfile extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = UmkmProfileResource::class;
}
