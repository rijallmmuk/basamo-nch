<?php

namespace App\Filament\Resources\Modules;

use App\Filament\Resources\Modules\Pages\CreateMateris;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Modules\Pages\ViewModule;
use App\Filament\Resources\Modules\RelationManagers\MaterisRelationManager;
use App\Filament\Resources\Modules\Schemas\ModuleForm;
use App\Filament\Resources\Modules\Schemas\ModuleInfolist;
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

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'SLC';
    }

    // Seluruh peran back-office melihat menu; policy dan query membatasi tindakan
    // serta data sesuai kepemilikan dan cakupan nagarinya.
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);
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

    public static function infolist(Schema $schema): Schema
    {
        return ModuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModulesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->withCount('materis')
            ->with(['pelatihan.tema', 'prerequisite', 'pretest', 'evaluasiKegiatan', 'creator', 'media'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->when($user, fn (Builder $query) => $query->visibleTo($user));
    }

    public static function getRelations(): array
    {
        return [
            MaterisRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'create-materis' => CreateMateris::route('/{record}/materis/create'),
            'view' => ViewModule::route('/{record}'),
            'edit' => EditModule::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['pelatihan.nagaris', 'creator', 'media', 'prerequisite', 'pretest', 'evaluasiKegiatan'])
            ->when($user, fn (Builder $query) => $query->visibleTo($user))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
