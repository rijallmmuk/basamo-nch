<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Enums\ModuleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
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
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('Cover')
                    ->collection('cover')
                    ->conversion('card')
                    ->height(36)
                    ->defaultImageUrl(asset('images/default-module-cover.svg')),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->default('Global')
                    ->icon(fn ($state): ?string => $state === 'Global' ? 'heroicon-o-globe-alt' : null)
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('pages_count')
                    ->label('Materi')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('quiz_exists')
                    ->label('Kuis')
                    ->boolean(),

                TextColumn::make('estimated_minutes')
                    ->label('Durasi')
                    ->formatStateUsing(fn (?int $state): string => $state ? $state.' mnt' : '—')
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
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
                    ->placeholder('Semua (termasuk global)'),

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
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc');
    }
}
