<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $title = 'Jawaban Peserta';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('soal')
                    ->label('Soal')
                    ->content(fn ($record) => $record?->question?->question ?? '-')
                    ->columnSpanFull(),

                Placeholder::make('jawaban_warga')
                    ->label('Jawaban Warga')
                    ->content(fn ($record) => $record?->answer_text ?? '(tidak ada jawaban teks)')
                    ->columnSpanFull(),

                TextInput::make('score_given')
                    ->label('Nilai')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText(fn ($record) => 'Maks: '.($record?->question?->points ?? '?').' poin')
                    ->columnSpan(1),

                Textarea::make('feedback')
                    ->label('Feedback / Komentar')
                    ->rows(3)
                    ->columnSpan(1),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('answer_text')
            ->modifyQueryUsing(fn ($query) => $query->with('question'))
            ->columns([
                TextColumn::make('question.question')
                    ->label('Soal')
                    ->limit(70)
                    ->wrap(),

                TextColumn::make('question.type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'multiple_choice' => '🔘 Pilihan Ganda',
                        'essay' => '✍️ Essay',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'multiple_choice' => 'info',
                        'essay' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('answer_text')
                    ->label('Jawaban Essay')
                    ->limit(80)
                    ->wrap()
                    ->placeholder('(pilihan ganda)'),

                IconColumn::make('is_correct')
                    ->label('Benar?')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('score_given')
                    ->label('Nilai')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? $state.' / '.($record->question?->points ?? '?')
                        : null
                    ),

                TextColumn::make('feedback')
                    ->label('Feedback')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                EditAction::make()
                    ->label('Nilai')
                    ->visible(fn ($record): bool => ($record->question?->type ?? '') === 'essay'),
            ])
            ->toolbarActions([]);
    }
}
