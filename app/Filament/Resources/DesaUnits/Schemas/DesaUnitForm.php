<?php

namespace App\Filament\Resources\DesaUnits\Schemas;

use App\Models\Desa;
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
        // Sebutan sub-unit desa konteks (Jorong/Korong/Dusun/…) — dipakai untuk label.
        $desaId = auth()->user()?->managedDesaId();
        $sebutan = ($desaId ? Desa::find($desaId)?->jenisSubUnit?->nama : null) ?: 'Wilayah';

        return $schema
            ->components([
                Section::make("Data {$sebutan}")
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->schema([
                        // Pemilih desa hanya muncul bila tak ada desa konteks (super admin
                        // tanpa "Kelola Wilayah"). Dalam konteks, desa dipaksa di CreateDesaUnit.
                        Select::make('desa_id')
                            ->label('Desa')
                            ->relationship('desa', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (): bool => auth()->user()?->managedDesaId() === null)
                            ->columnSpanFull(),

                        TextInput::make('nama')
                            ->label("Nama {$sebutan}")
                            ->required()
                            ->maxLength(255)
                            ->helperText("Cukup namanya saja, tanpa kata \"{$sebutan}\". Mis. Koto Tuo.")
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                    $desaId = auth()->user()?->managedDesaId() ?? $get('desa_id');

                                    // Hanya bentrok dengan sub-unit AKTIF; nama bekas yang
                                    // sudah dihapus boleh dipakai ulang (lihat migrasi unique).
                                    return $rule->where('desa_id', $desaId)->withoutTrashed();
                                },
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
