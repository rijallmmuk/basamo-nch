<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Enums\ModuleStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Baris TIDAK dapat diklik — buka Ubah lewat aksi (sejajar, tak digabung ⋮).
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('Cover')
                    ->collection('cover')
                    ->conversion('card')
                    ->height(36)
                    ->defaultImageUrl(asset('images/default-module-cover.svg')),

                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->default('Semua')
                    ->icon(fn ($state): ?string => $state === 'Semua' ? 'heroicon-o-globe-alt' : null)
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                IconColumn::make('quiz_exists')
                    ->label('Kuis')
                    ->boolean(),

                // — Kolom sekunder (default tersembunyi, muncul lewat "Kolom") —
                // Prasyarat: relevan utk LMS (dependensi modul), tapi sering kosong → sekunder.
                TextColumn::make('prerequisite.judul')
                    ->label('Prasyarat')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ModuleStatus::class),

                SelectFilter::make('desa')
                    ->label('Desa')
                    ->relationship('desa', 'nama')
                    ->placeholder('Semua (termasuk semua-desa)'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->color('warning'),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->reorderable('urutan')
            ->defaultSort('urutan', 'asc');
    }
}
