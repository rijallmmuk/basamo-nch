<?php

namespace App\Filament\Resources\Quizzes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Warga')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quiz.title')
                    ->label('Kuis')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('quiz.module.title')
                    ->label('Modul')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_review' => 'Menunggu Review',
                        'passed' => 'Lulus',
                        'failed' => 'Tidak Lulus',
                        'in_progress' => 'Sedang Dikerjakan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending_review' => 'warning',
                        'passed' => 'success',
                        'failed' => 'danger',
                        'in_progress' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('score')
                    ->label('Nilai')
                    ->suffix('%')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('reviewer.name')
                    ->label('Di-review oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending_review' => 'Menunggu Review',
                        'passed' => 'Lulus',
                        'failed' => 'Tidak Lulus',
                        'in_progress' => 'Sedang Dikerjakan',
                    ])
                    ->default('pending_review'),
            ])
            ->recordActions([
                EditAction::make()->label('Buka'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }
}
