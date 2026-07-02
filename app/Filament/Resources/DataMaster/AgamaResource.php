<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\Pages\ManageAgama;
use App\Models\Agama;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AgamaResource extends LookupResource
{
    protected static ?string $model = Agama::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 1;

    protected static string $usageRelation = 'penduduk';

    protected static string $usageLabel = 'warga';

    public static function getModelLabel(): string
    {
        return 'Agama';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Agama';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAgama::route('/'),
        ];
    }
}
