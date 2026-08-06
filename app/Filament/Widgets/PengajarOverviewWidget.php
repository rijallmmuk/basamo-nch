<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Discussions\DiscussionResource;
use App\Filament\Resources\EvaluasiKegiatans\EvaluasiKegiatanResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Support\Dashboard\PengajarOverviewData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PengajarOverviewWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('pengajar');
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $urls = [
            'Konten SLC Saya' => ModuleResource::getUrl('index'),
            'Warga Aktif Belajar' => SlcRekapResource::getUrl('index'),
            'Evaluasi Kegiatan' => EvaluasiKegiatanResource::getUrl('index'),
            'Forum Belum Dibaca' => DiscussionResource::getUrl('index'),
        ];

        return array_map(
            fn (array $stat): Stat => Stat::make($stat['label'], (string) $stat['value'])
                ->description($stat['description'])
                ->descriptionIcon($stat['icon'])
                ->color($stat['color'])
                ->chart($stat['chart'] ?? null)
                ->url($urls[$stat['label']] ?? null),
            PengajarOverviewData::stats($user),
        );
    }
}
