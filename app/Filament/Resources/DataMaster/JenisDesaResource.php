<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\Pages\ManageJenisDesa;
use App\Models\JenisDesa;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class JenisDesaResource extends LookupResource
{
    protected static ?string $model = JenisDesa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 4;

    protected static string $usageRelation = 'desas';

    protected static string $usageLabel = 'desa';

    public static function getModelLabel(): string
    {
        return 'Penyebutan Desa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Penyebutan Desa';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageJenisDesa::route('/'),
        ];
    }
}
