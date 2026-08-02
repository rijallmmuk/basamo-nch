<?php

namespace App\Filament\Resources\Evaluasis\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

abstract class EditEvaluasi extends EditRecord
{
    use RedirectsToView;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
