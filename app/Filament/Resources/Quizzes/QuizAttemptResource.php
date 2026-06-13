<?php

namespace App\Filament\Resources\Quizzes;

use App\Filament\Resources\Quizzes\Pages\ListQuizAttempts;
use App\Filament\Resources\Quizzes\Pages\ReviewQuizAttempt;
use App\Filament\Resources\Quizzes\Schemas\QuizAttemptInfoSchema;
use App\Filament\Resources\Quizzes\Tables\QuizAttemptsTable;
use App\Models\QuizAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'LMS';
    }

    public static function getNavigationLabel(): string
    {
        return 'Review Essay';
    }

    public static function getModelLabel(): string
    {
        return 'Ujian';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Antrian Review';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return QuizAttemptInfoSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizAttemptsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'quiz.module', 'reviewer']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAttempts::route('/'),
            'edit' => ReviewQuizAttempt::route('/{record}/edit'),
        ];
    }
}
