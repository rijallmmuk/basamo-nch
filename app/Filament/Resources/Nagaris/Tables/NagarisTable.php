<?php

namespace App\Filament\Resources\Nagaris\Tables;

use App\Enums\ActiveStatus;
use App\Filament\Resources\Nagaris\NagariResource;
use App\Filament\Resources\Nagaris\Support\NagariActions;
use App\Models\Nagari;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class NagarisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Nagari $record): string => NagariResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nama & Kabupaten')
                    ->weight(FontWeight::Bold)
                    ->description(fn (Nagari $record): string => $record->kabupaten ?? '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('wilayah_kode')
                    ->label('Kode Wilayah')
                    ->fontFamily(FontFamily::Mono)
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('Kode wilayah disalin')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('warga_count')
                    ->label('Warga')
                    ->numeric()
                    ->icon('heroicon-m-users')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('umkm_count')
                    ->label('UMKM')
                    ->numeric()
                    ->icon('heroicon-m-building-storefront')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('modul_count')
                    ->label('Modul')
                    ->numeric()
                    ->icon('heroicon-m-book-open')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('sdgs_skor')
                    ->label('Skor SDGs')
                    ->numeric(2)
                    ->suffix('%')
                    ->icon('heroicon-m-chart-bar')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ActiveStatus::class),

                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label(fn (): string => (auth()->user()?->isDpmd() ?? false) ? 'Lihat detail' : 'Buka detail')
                        ->icon('heroicon-o-eye'),
                    ...NagariActions::navigation(),
                    ...NagariActions::lifecycle(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('nama');
    }
}
