<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\UmkmOverviewData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UmkmOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasUmkmAccess();
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return array_map(
            fn (array $stat): Stat => Stat::make($stat['label'], (string) $stat['value'])
                ->description($stat['description'])
                ->descriptionIcon($stat['icon'])
                ->color($stat['color'])
                // Kartu berpranala dirender sebagai tautan oleh Filament, sehingga
                // seluruh kartunya dapat diklik.
                ->url($stat['url'] ?? null),
            UmkmOverviewData::stats($user),
        );
    }
}
