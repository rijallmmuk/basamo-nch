<?php

namespace App\Filament\Resources\Evaluasis;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\Evaluasis\RelationManagers\PertanyaansRelationManager;
use App\Filament\Resources\Evaluasis\Schemas\EvaluasiForm;
use App\Filament\Resources\Evaluasis\Schemas\EvaluasiInfolist;
use App\Filament\Resources\Evaluasis\Tables\EvaluasisTable;
use App\Models\Evaluasi;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class EvaluasiResource extends Resource
{
    protected static ?string $model = Evaluasi::class;

    /** Jenis yang dikelola menu ini. Dua turunannya: Pre-test dan Evaluasi Kegiatan. */
    abstract public static function jenis(): JenisEvaluasi;

    public static function getNavigationGroup(): ?string
    {
        return 'SLC';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);
    }

    public static function getModelLabel(): string
    {
        return static::jenis()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::jenis()->getLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return EvaluasiForm::configure($schema, static::jenis());
    }

    public static function infolist(Schema $schema): Schema
    {
        return EvaluasiInfolist::configure($schema, static::jenis());
    }

    public static function table(Table $table): Table
    {
        return EvaluasisTable::configure($table, static::jenis());
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->where('jenis', static::jenis())
            ->with('module.pelatihan.tema')
            ->withCount('pertanyaans')
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->when(
                $user && ($user->isOperator() || $user->isPengajar()),
                fn (Builder $query) => $query->whereHas('module', fn (Builder $modules) => $modules->visibleTo($user)),
            );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getRecordRouteBindingEloquentQuery()
            ->where('jenis', static::jenis())
            ->with(['module.pelatihan.tema', 'pertanyaans.opsis'])
            ->withCount('pertanyaans')
            ->when(
                $user && ($user->isOperator() || $user->isPengajar()),
                fn (Builder $query) => $query->whereHas('module', fn (Builder $modules) => $modules->visibleTo($user)),
            )
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            PertanyaansRelationManager::class,
        ];
    }
}
