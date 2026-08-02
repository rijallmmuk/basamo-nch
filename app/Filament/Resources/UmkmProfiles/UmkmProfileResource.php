<?php

namespace App\Filament\Resources\UmkmProfiles;

use App\Filament\Resources\UmkmProfiles\Pages\CreateUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\EditUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\ListUmkmProfiles;
use App\Filament\Resources\UmkmProfiles\Pages\ViewUmkmProfile;
use App\Filament\Resources\UmkmProfiles\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UmkmProfiles\Schemas\UmkmProfileForm;
use App\Filament\Resources\UmkmProfiles\Schemas\UmkmProfileInfolist;
use App\Filament\Resources\UmkmProfiles\Tables\UmkmProfilesTable;
use App\Models\UmkmProfile;
use App\Support\NagariContext;
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

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Selalu tampil di sidebar (2026-07-14, keputusan user: "kuasa penuh" superadmin) —
    // klik langsung tanpa konteks otomatis diarahkan ke nagari pertama
    // (lihat ListUmkmProfiles::mount / NagariContext::ensureDefault), bukan buntu 403.
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd'])
            || auth()->user()?->hasUmkmAccess())
            && parent::canAccess();
    }

    public static function getModelLabel(): string
    {
        return static::isSelfService() ? 'Usaha Saya' : 'UMKM';
    }

    public static function getPluralModelLabel(): string
    {
        return static::isSelfService() ? 'Usaha Saya' : 'UMKM';
    }

    public static function getNavigationLabel(): string
    {
        return static::isSelfService() ? 'Kelola Usaha' : 'UMKM';
    }

    public static function form(Schema $schema): Schema
    {
        return UmkmProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UmkmProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UmkmProfilesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getEloquentQuery()
                ->with(['nagari', 'owner'])
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

    /**
     * Ter-scope ke nagari yang sedang dikelola: operator → nagarinya; super admin →
     * nagari konteks (NagariContext). Tanpa konteks, super admin tak bisa akses (canAccess).
     */
    protected static function scopeToActor(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->usesUmkmSelfService()) {
            return $query->where('user_id', $user->getKey());
        }

        $nagariId = $user?->managedNagariId(NagariContext::UMKM_PROFIL);

        if ($nagariId !== null) {
            $query->forNagari($nagariId);
        }

        return $query;
    }

    public static function isSelfService(): bool
    {
        return auth()->user()?->usesUmkmSelfService() ?? false;
    }

    /** Profil baru hanya diisi sendiri oleh warga setelah akses UMKM diberikan. */
    public static function canCreate(): bool
    {
        return static::isSelfService() && parent::canCreate();
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
            'view' => ViewUmkmProfile::route('/{record}'),
            'edit' => EditUmkmProfile::route('/{record}/edit'),
        ];
    }
}
