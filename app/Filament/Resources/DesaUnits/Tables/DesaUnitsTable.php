<?php

namespace App\Filament\Resources\DesaUnits\Tables;

use App\Filament\Resources\DesaUnits\DesaUnitResource;
use App\Models\DesaUnit;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DesaUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Klik baris → buka Edit.
            ->recordUrl(fn (DesaUnit $record): string => DesaUnitResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
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
                SelectFilter::make('desa')
                    ->label('Desa')
                    ->relationship('desa', 'nama')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])->tooltip('Aksi'),
            ])
            ->defaultSort('nama', 'asc');
    }
}
