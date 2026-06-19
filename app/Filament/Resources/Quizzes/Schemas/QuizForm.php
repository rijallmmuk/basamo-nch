<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('module_id')
                    ->label('Modul')
                    ->relationship(
                        name: 'module',
                        titleAttribute: 'title',
                        // Hanya tampilkan modul yang BELUM punya kuis (1 modul = 1 kuis).
                        // Saat edit, tetap sertakan modul kuis ini sendiri.
                        modifyQueryUsing: function (Builder $query, ?Model $record) {
                            $query->where(function (Builder $q) use ($record) {
                                $q->whereDoesntHave('quiz');
                                if ($record?->module_id) {
                                    $q->orWhere('id', $record->module_id);
                                }
                            });

                            // nagari_admin hanya boleh membuat kuis untuk modul nagarinya.
                            if (auth()->user()?->isNagariAdmin()) {
                                $query->where('nagari_id', auth()->user()->nagari_id);
                            }
                        },
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Judul Kuis')
                    ->helperText('Kosongkan untuk memakai "Kuis: {judul modul}" otomatis.')
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('passing_score')
                    ->label('Nilai Kelulusan')
                    ->helperText('Nilai minimum untuk lulus (skala 0–100).')
                    ->numeric()
                    ->default(70)
                    ->minValue(1)
                    ->maxValue(100)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('max_attempts')
                    ->label('Maks. Percobaan')
                    ->numeric()
                    ->default(3)
                    ->minValue(0)
                    ->maxValue(255)
                    ->helperText('Isi 0 untuk percobaan tidak terbatas.')
                    ->required()
                    ->columnSpan(1),
            ])
            ->columns(2);
    }
}
