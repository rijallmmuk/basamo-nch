<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Beritas\BeritaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBeritas extends ListRecords
{
    use HasListTitle;

    protected static string $resource = BeritaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tulis Berita / Pengumuman')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
