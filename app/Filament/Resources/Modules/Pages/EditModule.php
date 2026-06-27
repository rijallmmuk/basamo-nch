<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Modules\ModuleResource;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ModuleResource::class;
}
