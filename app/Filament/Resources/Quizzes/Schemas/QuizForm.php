<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('module_id')
                    ->label('Modul')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Judul Kuis')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('passing_score')
                    ->label('Nilai Kelulusan')
                    ->numeric()
                    ->default(70)
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required()
                    ->columnSpan(1),

                TextInput::make('max_attempts')
                    ->label('Maks. Percobaan')
                    ->numeric()
                    ->default(3)
                    ->minValue(1)
                    ->helperText('0 = tidak terbatas')
                    ->required()
                    ->columnSpan(1),
            ])
            ->columns(2);
    }
}
