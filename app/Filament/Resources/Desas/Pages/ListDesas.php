<?php

namespace App\Filament\Resources\Desas\Pages;

use App\Filament\Resources\Desas\DesaResource;
use App\Support\DesaContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesas extends ListRecords
{
    protected static string $resource = DesaResource::class;

    public function mount(): void
    {
        parent::mount();

        // Kembali ke daftar Desa → keluar dari konteks "kelola warga desa X".
        DesaContext::clear();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
