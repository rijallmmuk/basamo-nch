<?php

namespace App\Filament\Resources\Wilayahs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WilayahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nagari.nama')
                    ->label('Nagari')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                TextColumn::make('users_count')
                    ->label('Warga')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('nagari')
                    ->label('Nagari')
                    ->relationship('nagari', 'nama')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama', 'asc');
    }
}
