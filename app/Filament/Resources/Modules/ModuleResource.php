<?php

namespace App\Filament\Resources\Modules;

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Modules\RelationManagers\PagesRelationManager;
use App\Filament\Resources\Modules\Schemas\ModuleForm;
use App\Filament\Resources\Modules\Tables\ModulesTable;
use App\Models\Module;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'LMS';
    }

    public static function getModelLabel(): string
    {
        return 'Modul';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Modul';
    }

    public static function form(Schema $schema): Schema
    {
        return ModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModulesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['nagari', 'creator'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return static::scopeToNagari($query);
    }

    /**
     * nagari_admin hanya boleh mengakses modul nagarinya sendiri.
     * super_admin melihat semua (termasuk modul global).
     */
    protected static function scopeToNagari(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isNagariAdmin()) {
            $query->where('nagari_id', $user->nagari_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            PagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'edit' => EditModule::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToNagari(
            parent::getRecordRouteBindingEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
        );
    }
}
