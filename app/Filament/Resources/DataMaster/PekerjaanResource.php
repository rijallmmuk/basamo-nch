<?php

namespace App\Filament\Resources\DataMaster;

use App\Filament\Resources\DataMaster\Pages\ManagePekerjaan;
use App\Models\Pekerjaan;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

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

    protected static function extraFormFields(): array
    {
        return [
            TextInput::make('kode')
                ->label('Kode')
                ->helperText('Kode pekerjaan standar KTP (mis. 01–99).')
                ->required()
                ->maxLength(4)
                ->unique(ignoreRecord: true),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('kode')
                ->label('Kode')
                ->badge()
                ->color('gray')
                ->sortable()
                ->alignCenter(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePekerjaan::route('/'),
        ];
    }
}
