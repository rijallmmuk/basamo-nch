<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\Pages\ManagePekerjaan;
use App\Models\Pekerjaan;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PekerjaanResource extends LookupResource
{
    protected static ?string $model = Pekerjaan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 3;

    protected static string $usageRelation = 'penduduk';

    protected static string $usageLabel = 'warga';

    protected static int $namaMaxLength = 100;

    public static function getModelLabel(): string
    {
        return 'Pekerjaan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pekerjaan';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePekerjaan::route('/'),
        ];
    }
}
