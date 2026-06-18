<?php

namespace App\Filament\Resources\Nagaris\Tables;

use App\Filament\Resources\Nagaris\NagariResource;
use App\Models\Nagari;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class NagarisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kabupaten')
                    ->label('Kabupaten/Kota')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('users_count')
                    ->label('Warga')
                    ->counts('users')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('modules_count')
                    ->label('Modul')
                    ->counts('modules')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Nonaktif')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif']),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(fn (Nagari $record, DeleteAction $action) => NagariResource::guardAgainstDependents($record, $action)),
            ])
            ->toolbarActions([
                // Tanpa hapus massal: penghapusan nagari harus per-record agar guard
                // anti-orphan (warga/modul) berjalan. Restore massal tetap aman.
                RestoreBulkAction::make(),
            ])
            ->defaultSort('nama');
    }
}
