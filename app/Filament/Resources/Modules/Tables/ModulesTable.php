<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Enums\ModuleStatus;
use App\Filament\Resources\Discussions\DiscussionResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Enums\IconPosition;
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
            // Klik baris → buka Edit.
            ->recordUrl(fn (Module $record): string => ModuleResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('Cover')
                    ->collection('cover')
                    ->conversion('card')
                    ->height(36)
                    ->defaultImageUrl(asset('images/default-module-cover.svg'))
                    ->alignCenter(),

                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    // Penanda baris terhapus — terlihat sekilas saat filter "termasuk terhapus" aktif.
                    ->icon(fn (Module $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (Module $record): ?string => $record->trashed() ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y') : null),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->default('Semua')
                    ->icon(fn ($state): ?string => $state === 'Semua' ? 'heroicon-o-globe-alt' : null)
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter(),

                IconColumn::make('quiz_exists')
                    ->label('Kuis')
                    ->boolean()
                    ->alignCenter(),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
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
            // Semua aksi baris dalam satu menu ⋮ (pola sama dgn halaman Desa).
            ->recordActions([
                ActionGroup::make([
                    // Integrasi kuis: modul ber-kuis → Edit Kuis (relation manager Soal),
                    // modul tanpa kuis → Buat Kuis dgn modul terpilih otomatis. Tersembunyi
                    // untuk modul terarsip (kuis tak dikelola saat modul dihapus).
                    Action::make('kelolaKuis')
                        ->label(fn (Module $record): string => $record->quiz ? 'Kelola Kuis' : 'Tambah Kuis')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('info')
                        ->visible(fn (Module $record): bool => ! $record->trashed())
                        ->url(fn (Module $record): string => $record->quiz
                            ? QuizResource::getUrl('edit', ['record' => $record->quiz])
                            : QuizResource::getUrl('create', ['module_id' => $record->id])),
                    // Integrasi diskusi: buka daftar Diskusi (pertanyaan warga) ter-filter
                    // modul ini untuk dimoderasi. Diskusi dibuat warga di portal — admin
                    // tak membuat, hanya mengelola; tersembunyi utk modul terarsip.
                    Action::make('kelolaDiskusi')
                        ->label('Kelola Diskusi')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->visible(fn (Module $record): bool => ! $record->trashed())
                        // Query key 'filters' (bukan 'tableFilters') — Filament mem-bind
                        // $tableFilters via #[Url(as: 'filters')]. Filter live (deferFilters
                        // false di DiscussionsTable) agar tautan ini langsung menyaring.
                        ->url(fn (Module $record): string => DiscussionResource::getUrl('index', [
                            'filters' => ['module' => ['value' => $record->id]],
                        ])),
                    EditAction::make()
                        ->color('warning'),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->icon('heroicon-m-squares-2x2')
                    ->tooltip('Aksi'),
            ])
            ->reorderable('urutan')
            ->defaultSort('urutan', 'asc');
    }
}
