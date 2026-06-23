<?php

namespace App\Filament\Resources\Desas\Tables;

use App\Enums\ActiveStatus;
use App\Filament\Resources\Desas\DesaResource;
use App\Models\Desa;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DesasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Klik baris → buka Edit.
            ->recordUrl(fn (Desa $record): string => DesaResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->formatStateUsing(fn (Desa $record): string => $record->nama_lengkap)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('wilayah_kode')
                    ->label('Kode Wilayah')
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

                TextColumn::make('warga_count')
                    ->label('Warga')
                    ->counts('warga')
                    ->badge()
                    ->color('info')
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
                ActionGroup::make([
                    // Hapus per-record agar guard anti-orphan (warga/modul) berjalan.
                    DeleteAction::make()
                        ->before(fn (Desa $record, DeleteAction $action) => DesaResource::guardAgainstDependents($record, $action))
                        ->after(fn (Desa $record) => DesaResource::archiveAdmin($record)),
                    RestoreAction::make()
                        ->after(fn (Desa $record) => DesaResource::restoreAdmin($record)),
                    ForceDeleteAction::make()
                        ->before(fn (Desa $record, ForceDeleteAction $action) => DesaResource::guardAgainstDependents($record, $action, includeTrashed: true)),
                ])->tooltip('Aksi'),
            ])
            ->defaultSort('nama');
    }
}
