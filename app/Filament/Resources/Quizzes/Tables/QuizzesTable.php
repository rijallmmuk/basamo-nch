<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Klik baris → buka Edit (pola sama dgn tabel Modul).
            ->recordUrl(fn (Quiz $record): string => QuizResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('module.judul')
                    ->label('Modul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('questions_count')
                    ->label('Soal')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('nilai_lulus')
                    ->label('Nilai Lulus')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('maks_percobaan')
                    ->label('Maks. Coba')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state === 0 ? '∞' : $state)
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            // Semua aksi baris dalam satu menu ⋮ (pola sama dgn tabel Modul).
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->icon('heroicon-m-squares-2x2')
                    ->tooltip('Aksi'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
