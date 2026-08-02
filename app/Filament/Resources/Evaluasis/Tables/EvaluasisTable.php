<?php

namespace App\Filament\Resources\Evaluasis\Tables;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\EvaluasiKegiatans\EvaluasiKegiatanResource;
use App\Filament\Resources\Pretests\PretestResource;
use App\Models\Evaluasi;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EvaluasisTable
{
    public static function configure(Table $table, ?JenisEvaluasi $jenis = null): Table
    {
        return $table
            ->recordUrl(fn (Evaluasi $record): ?string => auth()->user()?->can('view', $record)
                ? self::resourceUntuk($jenis)::getUrl('view', ['record' => $record])
                : null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('module.judul')
                    ->label('Modul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->icon(fn (Evaluasi $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (Evaluasi $record): string => $record->trashed()
                        ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y')
                        : ($record->module?->judul ?? 'Modul tidak tersedia')),

                TextColumn::make('pertanyaans_count')
                    ->label('Soal')
                    ->counts('pertanyaans')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('nilai_lulus')
                    ->label('Nilai Lulus')
                    ->sortable()
                    ->alignCenter()
                    ->visible($jenis !== JenisEvaluasi::Pretest),

                TextColumn::make('maks_percobaan')
                    ->label('Batas Percobaan')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => (int) $state === 0 ? 'Tidak dibatasi' : $state.' kali')
                    ->alignCenter()
                    ->visible($jenis !== JenisEvaluasi::Pretest),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn (Evaluasi $record): bool => ! $record->trashed()
                            && ! $record->isPretest()),
                    Action::make('tambahSoal')
                        ->label('Tambah Soal')
                        ->icon('heroicon-o-plus')
                        ->color('primary')
                        ->visible(fn (Evaluasi $record): bool => ! $record->trashed())
                        ->authorize(fn (Evaluasi $record): bool => (auth()->user()?->can('update', $record) ?? false))
                        ->url(fn (Evaluasi $record): string => self::resourceUntuk($jenis)::getUrl(
                            'create-questions',
                            ['record' => $record],
                        )),
                    Action::make('previewWarga')
                        ->label('Pratinjau Warga')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->openUrlInNewTab()
                        ->visible(fn (Evaluasi $record): bool => ! $record->trashed()
                            && $record->module !== null)
                        ->url(function (Evaluasi $record) use ($jenis): string {
                            $module = $record->relationLoaded('module')
                                ? $record->module
                                : $record->module()->first();

                            $route = $jenis === JenisEvaluasi::Pretest
                                ? 'admin.preview.modules.pretest.show'
                                : 'admin.preview.modules.evaluasi.show';

                            return route($route, ['module' => $module->slug]);
                        }),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    /** Menu mana yang memiliki jenis ini, dipakai untuk merakit URL baris. */
    private static function resourceUntuk(?JenisEvaluasi $jenis): string
    {
        return $jenis === JenisEvaluasi::Pretest
            ? PretestResource::class
            : EvaluasiKegiatanResource::class;
    }
}
