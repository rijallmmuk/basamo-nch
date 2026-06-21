<?php

namespace App\Filament\Resources\Desas\Tables;

use App\Enums\ActiveStatus;
use App\Filament\Resources\Desas\DesaResource;
use App\Models\Desa;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DesasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

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
                    ->options(ActiveStatus::class),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(fn (Desa $record, DeleteAction $action) => DesaResource::guardAgainstDependents($record, $action)),
            ])
            ->toolbarActions([
                // Tanpa hapus massal: penghapusan desa harus per-record agar guard
                // anti-orphan (warga/modul) berjalan. Restore massal tetap aman.
                RestoreBulkAction::make(),
            ])
            ->defaultSort('nama');
    }
}
