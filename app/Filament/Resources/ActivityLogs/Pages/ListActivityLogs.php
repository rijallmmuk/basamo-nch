<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    use HasListTitle;

    // Read-only: tak ada aksi buat.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
