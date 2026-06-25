<?php

namespace App\Filament\Resources\UmkmProfiles\Tables;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UmkmProfilesTable
{
    public static function configure(Table $table): Table
    {
        // Ter-scope ke satu desa (desa_admin, atau super admin via "Kelola › UMKM")
        // → kolom Desa tak relevan.
        $scopedToDesa = auth()->user()?->managedDesaId() !== null;

        return $table
            // Klik baris → buka Edit.
            ->recordUrl(fn (UmkmProfile $record): string => UmkmProfileResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

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
                    ->visible(! $scopedToDesa),

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge(),

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
                    ->options(ActiveStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
