<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\Pages\ManageJenisSubUnit;
use App\Models\JenisSubUnit;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class JenisSubUnitResource extends LookupResource
{
    protected static ?string $model = JenisSubUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 5;

    protected static string $usageRelation = 'desas';

    protected static string $usageLabel = 'desa';

    public static function getModelLabel(): string
    {
        return 'Sebutan Sub-Unit';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sebutan Sub-Unit';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageJenisSubUnit::route('/'),
        ];
    }
}
