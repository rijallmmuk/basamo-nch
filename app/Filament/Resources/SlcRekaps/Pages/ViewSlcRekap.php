<?php

namespace App\Filament\Resources\SlcRekaps\Pages;

use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Services\SlcRekapService;
use App\Support\NagariContext;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewSlcRekap extends ViewRecord
{
    protected static string $resource = SlcRekapResource::class;

    protected string $view = 'filament.resources.slc-rekaps.pages.view-slc-rekap';

    public function getTitle(): string
    {
        return 'Rekap Belajar Warga: '.$this->record->name;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /** @return array<string, mixed> */
    public function getRekapDataProperty(): array
    {
        $actor = auth()->user();

        abort_unless($actor, 403);

        return app(SlcRekapService::class)->detailFor(
            $this->record,
            $actor,
            $actor->managedNagariId(NagariContext::LMS_REKAP),
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
