<?php

namespace App\Filament\Resources\Wilayahs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class WilayahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Hanya super_admin memilih nagari; nagari_admin dipaksa ke nagarinya (CreateWilayah).
                Select::make('nagari_id')
                    ->label('Nagari')
                    ->relationship('nagari', 'nama')
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
                            $nagariId = auth()->user()?->isNagariAdmin()
                                ? auth()->user()->nagari_id
                                : $get('nagari_id');

                            return $rule->where('nagari_id', $nagariId);
                        },
                    )
                    ->columnSpanFull(),
            ]);
    }
}
