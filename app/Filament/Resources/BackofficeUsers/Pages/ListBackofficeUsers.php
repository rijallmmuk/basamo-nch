<?php

namespace App\Filament\Resources\BackofficeUsers\Pages;

use App\Filament\Resources\BackofficeUsers\BackofficeUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackofficeUsers extends ListRecords
{
    protected static string $resource = BackofficeUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->color('primary'),
        ];
    }
}
