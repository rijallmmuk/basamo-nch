<?php

namespace App\Filament\Resources\Discussions\Pages;

use App\Filament\Resources\Discussions\DiscussionResource;
use Filament\Resources\Pages\ListRecords;

class ListDiscussions extends ListRecords
{
    protected static string $resource = DiscussionResource::class;

    // Moderasi saja — diskusi dibuat warga di portal, bukan di sini.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
