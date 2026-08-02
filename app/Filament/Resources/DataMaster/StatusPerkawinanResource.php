<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\Pages\ManageStatusPerkawinan;
use App\Models\StatusPerkawinan;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StatusPerkawinanResource extends LookupResource
{
    protected static ?string $model = StatusPerkawinan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 5;

    protected static string $usageRelation = 'penduduk';

    protected static string $usageLabel = 'warga';

    public static function getModelLabel(): string
    {
        return 'Status Perkawinan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Status Perkawinan';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStatusPerkawinan::route('/'),
        ];
    }
}
