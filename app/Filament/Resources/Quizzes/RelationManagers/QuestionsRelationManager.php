<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Daftar Soal';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipe Soal')
                    ->options([
                        'multiple_choice' => '🔘 Pilihan Ganda',
                        'essay' => '✍️ Essay',
                    ])
                    ->default('multiple_choice')
                    ->required()
                    ->live()
                    ->columnSpan(1),

                TextInput::make('points')
                    ->label('Poin')
                    ->numeric()
                    ->default(10)
                    ->minValue(1)
                    ->required()
                    ->columnSpan(1),

                Textarea::make('question')
                    ->label('Soal')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Repeater::make('options')
                    ->label('Pilihan Jawaban')
                    ->relationship('options', fn ($query) => $query->orderBy('order'))
                    ->schema([
                        TextInput::make('option_text')
                            ->label('Teks Pilihan')
                            ->required()
                            ->columnSpan(5),

                        Toggle::make('is_correct')
                            ->label('Benar')
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(6)
                    ->minItems(2)
                    ->maxItems(5)
                    ->addActionLabel('+ Tambah Pilihan')
                    ->reorderable()
                    ->cloneable(false)
                    ->visible(fn ($get) => $get('type') === 'multiple_choice')
                    ->helperText('Tandai tepat satu pilihan sebagai jawaban benar.')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->reorderable('order')
            ->defaultSort('order', 'asc')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->width('40px'),

                TextColumn::make('question')
                    ->label('Soal')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('type')
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

                TextColumn::make('points')
                    ->label('Poin')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Soal'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
