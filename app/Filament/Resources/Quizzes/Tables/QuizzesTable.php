<?php

namespace App\Filament\Resources\Quizzes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module.title')
                    ->label('Modul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('title')
                    ->label('Judul Kuis')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('questions_count')
                    ->label('Soal')
                    ->counts('questions')
                    ->badge()
                    ->color('info'),

                TextColumn::make('passing_score')
                    ->label('Nilai Lulus')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('max_attempts')
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
