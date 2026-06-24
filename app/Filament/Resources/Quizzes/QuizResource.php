<?php

namespace App\Filament\Resources\Quizzes;

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\Pages\ListQuizzes;
use App\Filament\Resources\Quizzes\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\Quizzes\Schemas\QuizForm;
use App\Filament\Resources\Quizzes\Tables\QuizzesTable;
use App\Models\Quiz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'LMS';
    }

    // LMS difokuskan ke super admin; admin desa cukup disembunyikan dari sidebar.
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getModelLabel(): string
    {
        return 'Kuis';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kuis';
    }

    public static function form(Schema $schema): Schema
    {
        return QuizForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizzesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToDesa(
            parent::getEloquentQuery()
                ->with('module')
                ->withCount('questions')
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToDesa(parent::getRecordRouteBindingEloquentQuery());
    }

    /**
     * desa_admin hanya boleh mengakses kuis dari modul desanya sendiri.
     * super_admin melihat semua. Dipakai oleh listing & route-model binding (edit/URL langsung).
     */
    protected static function scopeToDesa(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            $query->whereHas('module', fn (Builder $q) => $q->where('desa_id', $user->desa_id));
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}
