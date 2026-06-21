<?php

namespace App\Filament\Resources\UmkmProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UmkmProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_usaha')
                    ->label('Nama usaha')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->searchable(),

                TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Nonaktif')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('umkm_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'nama'),
                SelectFilter::make('status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif']),
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
            ->defaultSort('created_at', 'desc');
    }
}
