<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Modules\ModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->color('primary'),
        ];
    }
}
