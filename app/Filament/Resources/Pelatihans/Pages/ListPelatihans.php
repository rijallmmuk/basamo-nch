<?php

namespace App\Filament\Resources\Pelatihans\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPelatihans extends ListRecords
{
    protected static string $resource = PelatihanResource::class;

    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pelatihan')
                ->color('primary'),
        ];
    }
}
