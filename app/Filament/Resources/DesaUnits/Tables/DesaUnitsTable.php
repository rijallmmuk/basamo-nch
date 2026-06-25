<?php

namespace App\Filament\Resources\DesaUnits\Tables;

use App\Filament\Resources\DesaUnits\DesaUnitResource;
use App\Models\Desa;
use App\Models\DesaUnit;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
        // Ter-scope ke satu desa (desa_admin, atau super admin via "Kelola Wilayah")
        // → kolom & filter Desa tak relevan.
        $desaId = auth()->user()?->managedDesaId();
        $scopedToDesa = $desaId !== null;

        // Sebutan sub-unit desa konteks (Jorong/Korong/Dusun/…) — diprefiks ke nama
        // di kolom agar jelas, mis. "Jorong Koto Tuo" (data hanya simpan namanya saja).
        $sebutan = ($desaId ? Desa::find($desaId)?->jenisSubUnit?->nama : null) ?: 'Wilayah';

        return $table
            // Baris TIDAK dapat diklik — buka Ubah lewat aksi (sejajar, tak digabung ⋮).
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('nama')
                    ->label('Nama')
                    // Prefiks sebutan agar jelas (mis. "Jorong Koto Tuo"); pencarian &
                    // pengurutan tetap memakai nilai `nama` mentah.
                    ->formatStateUsing(fn (string $state): string => "{$sebutan} {$state}")
                    ->searchable()
                    ->sortable(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visible(! $scopedToDesa),

                TextColumn::make('warga_count')
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
                    ->visible(! $scopedToDesa),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(fn (DesaUnit $record, DeleteAction $action) => DesaUnitResource::guardAgainstWarga($record, $action)),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->before(fn (DesaUnit $record, ForceDeleteAction $action) => DesaUnitResource::guardAgainstWarga($record, $action, includeTrashed: true)),
            ])
            ->defaultSort('nama', 'asc');
    }
}
