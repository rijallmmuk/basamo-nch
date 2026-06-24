<?php

namespace App\Filament\Resources\DesaUnits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class DesaUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Wilayah')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->schema([
                        // Hanya super_admin memilih desa; desa_admin dipaksa ke desanya (CreateDesaUnit).
                        Select::make('desa_id')
                            ->label('Desa')
                            ->relationship('desa', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                            ->columnSpanFull(),

                        TextInput::make('nama')
                            ->label('Nama wilayah')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama unit wilayah, mis. Jorong Koto Tuo.')
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                    $desaId = auth()->user()?->isDesaAdmin()
                                        ? auth()->user()->desa_id
                                        : $get('desa_id');

                                    return $rule->where('desa_id', $desaId);
                                },
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
