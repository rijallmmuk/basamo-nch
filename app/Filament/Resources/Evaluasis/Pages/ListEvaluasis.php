<?php

namespace App\Filament\Resources\Evaluasis\Pages;

use App\Filament\Concerns\HasListTitle;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

abstract class ListEvaluasis extends ListRecords
{
    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->color('primary'),
        ];
    }
}
