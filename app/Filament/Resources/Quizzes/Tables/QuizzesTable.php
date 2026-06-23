<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Klik baris → buka Edit.
            ->recordUrl(fn (Quiz $record): string => QuizResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('module.judul')
                    ->label('Modul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('questions_count')
                    ->label('Soal')
                    ->badge()
                    ->color('info'),

                TextColumn::make('nilai_lulus')
                    ->label('Nilai Lulus')
                    ->sortable(),

                TextColumn::make('maks_percobaan')
                    ->label('Maks. Coba')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state === 0 ? '∞' : $state),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
