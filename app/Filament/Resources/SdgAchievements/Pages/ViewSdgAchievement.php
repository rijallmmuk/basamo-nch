<?php

namespace App\Filament\Resources\SdgAchievements\Pages;

use App\Filament\Resources\SdgAchievements\SdgAchievementResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Viewer baca-saja — skor ditarik dari API Kemendesa (job terjadwal), tak ada
 * lagi form isi/ubah manual.
 */
class ViewSdgAchievement extends ViewRecord
{
    protected static string $resource = SdgAchievementResource::class;

    public function getTitle(): string
    {
        $goal = $this->record->goal;

        return $goal !== null
            ? 'Poin '.$goal->nomor.' · '.$goal->nama
            : 'Capaian SDGs';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
