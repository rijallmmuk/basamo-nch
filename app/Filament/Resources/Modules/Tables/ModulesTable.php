<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\EvaluasiKegiatans\EvaluasiKegiatanResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Modules\Pages\CreateMateris;
use App\Filament\Resources\Pretests\PretestResource;
use App\Models\Evaluasi;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModulesTable
{
    private static function canAccessEvaluasiAction(Module $record, JenisEvaluasi $jenis): bool
    {
        if ($record->trashed()) {
            return false;
        }

        $evaluasi = self::evaluasiFor($record, $jenis);

        if ($evaluasi !== null) {
            return auth()->user()?->can('view', $evaluasi) ?? false;
        }

        return false;
    }

    private static function evaluasiFor(Module $record, JenisEvaluasi $jenis): ?Evaluasi
    {
        $relation = $jenis === JenisEvaluasi::Pretest ? 'pretest' : 'evaluasiKegiatan';

        if ($record->relationLoaded($relation)) {
            return $record->{$relation};
        }

        return $record->{$relation}()->first();
    }

    public static function configure(Table $table, bool $withinPelatihan = false): Table
    {
        return $table
            ->recordUrl(fn (Module $record): string => ModuleResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->color('gray')
                    ->alignCenter(),

                ViewColumn::make('cover')
                    ->label('Cover')
                    ->view('filament.tables.module-cover')
                    ->alignCenter(),

                TextColumn::make('judul')
                    ->label('Judul Modul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->icon(fn (Module $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (Module $record): ?string => $record->trashed()
                        ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y')
                        : null),

                ...($withinPelatihan ? [] : [
                    TextColumn::make('pelatihan.tema.nama')
                        ->label('Pelatihan')
                        ->searchable()
                        ->sortable()
                        ->wrap()
                        ->placeholder('Tidak diketahui'),
                ]),

                TextColumn::make('materis_count')
                    ->label('Materi')
                    ->counts('materis')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                IconColumn::make('pretest_exists')
                    ->label('Pre-test')
                    ->state(fn (Module $record): bool => self::evaluasiFor($record, JenisEvaluasi::Pretest) !== null)
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('evaluasi_exists')
                    ->label('Evaluasi Kegiatan')
                    ->state(fn (Module $record): bool => self::evaluasiFor($record, JenisEvaluasi::Kegiatan) !== null)
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                ...($withinPelatihan ? [] : [
                    SelectFilter::make('nagari')
                        ->label('Nagari')
                        ->options(function (): array {
                            $actor = auth()->user();
                            $nagaris = Nagari::query()->orderBy('nama');

                            if ($actor?->isOperator()) {
                                $nagaris->whereKey($actor->nagari_id);
                            } elseif ($actor?->isPengajar()) {
                                $hasGlobalProgram = Pelatihan::query()
                                    ->visibleTo($actor)
                                    ->where('semua_nagari', true)
                                    ->exists();

                                if (! $hasGlobalProgram) {
                                    $nagaris->whereHas('pelatihans', fn (Builder $pelatihans) => $pelatihans
                                        ->visibleTo($actor));
                                }
                            }

                            return $nagaris->pluck('nama', 'id')->all();
                        })
                        ->query(fn (Builder $query, array $data): Builder => $query->when(
                            $data['value'],
                            fn (Builder $q, $nagariId) => $q->forNagari((int) $nagariId),
                        ))
                        ->placeholder('Semua nagari'),
                ]),

                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn (Module $record): bool => ! $record->trashed()),
                    ...($withinPelatihan ? [] : [
                        Action::make('tambahMateri')
                            ->label('Tambah Materi')
                            ->icon('heroicon-o-plus')
                            ->color('primary')
                            ->visible(fn (Module $record): bool => ! $record->trashed())
                            ->authorize(fn (Module $record): bool => (auth()->user()?->can('update', $record) ?? false))
                            ->url(fn (Module $record): string => CreateMateris::getUrl(['record' => $record])),
                    ]),
                    Action::make('previewWarga')
                        ->label('Pratinjau Warga')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->openUrlInNewTab()
                        ->visible(fn (Module $record): bool => ! $record->trashed())
                        ->url(function (Module $record): string {
                            $firstPage = $record->materis()->orderBy('urutan')->first();

                            if ($firstPage) {
                                return route('admin.preview.modules.materi.show', [
                                    'module' => $record->slug,
                                    'materi' => $firstPage->id,
                                ]);
                            }

                            return route('admin.preview.modules.show', ['module' => $record->slug]);
                        }),
                    self::evaluasiAction(JenisEvaluasi::Pretest),
                    self::evaluasiAction(JenisEvaluasi::Kegiatan),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort($withinPelatihan ? 'urutan' : 'updated_at', $withinPelatihan ? 'asc' : 'desc');
    }

    private static function evaluasiAction(JenisEvaluasi $jenis): Action
    {
        $resource = $jenis === JenisEvaluasi::Pretest
            ? PretestResource::class
            : EvaluasiKegiatanResource::class;

        return Action::make('kelola'.ucfirst($jenis->value))
            ->label(function (Module $record) use ($jenis): string {
                $evaluasi = self::evaluasiFor($record, $jenis);
                $nama = $jenis->getLabel();

                return match (true) {
                    $evaluasi === null => $nama,
                    auth()->user()?->can('update', $evaluasi) => 'Kelola '.$nama,
                    default => 'Lihat '.$nama,
                };
            })
            ->icon($jenis === JenisEvaluasi::Pretest
                ? 'heroicon-o-clipboard-document-list'
                : 'heroicon-o-clipboard-document-check')
            ->color('info')
            ->authorize(fn (Module $record): bool => self::canAccessEvaluasiAction($record, $jenis))
            ->visible(fn (Module $record): bool => self::canAccessEvaluasiAction($record, $jenis))
            ->url(function (Module $record) use ($jenis, $resource): string {
                $evaluasi = self::evaluasiFor($record, $jenis);

                return $evaluasi
                    ? $resource::getUrl('view', ['record' => $evaluasi])
                    : '#';
            });
    }
}
