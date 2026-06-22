<?php

namespace App\Filament\Resources\UmkmProfiles;

use App\Filament\Resources\UmkmProfiles\Pages\CreateUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\EditUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\ListUmkmProfiles;
use App\Filament\Resources\UmkmProfiles\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UmkmProfiles\Schemas\UmkmProfileForm;
use App\Filament\Resources\UmkmProfiles\Tables\UmkmProfilesTable;
use App\Models\UmkmProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UmkmProfileResource extends Resource
{
    protected static ?string $model = UmkmProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    public static function getModelLabel(): string
    {
        return 'Profil UMKM';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Profil UMKM';
    }

    public static function form(Schema $schema): Schema
    {
        return UmkmProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UmkmProfilesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getEloquentQuery()
                ->with(['desa', 'owner', 'category'])
                ->withoutGlobalScopes([SoftDeletingScope::class])
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getRecordRouteBindingEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class])
        );
    }

    /** desa_admin hanya UMKM di desanya; super_admin melihat semua. */
    protected static function scopeToActor(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            $query->forDesa($user->desa_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUmkmProfiles::route('/'),
            'create' => CreateUmkmProfile::route('/create'),
            'edit' => EditUmkmProfile::route('/{record}/edit'),
        ];
    }
}
