<?php

namespace App\Filament\Resources\UmkmProfiles;

use App\Filament\Resources\UmkmProfiles\Pages\CreateUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\EditUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\ListUmkmProfiles;
use App\Filament\Resources\UmkmProfiles\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UmkmProfiles\Schemas\UmkmProfileForm;
use App\Filament\Resources\UmkmProfiles\Tables\UmkmProfilesTable;
use App\Models\UmkmProfile;
use App\Support\DesaContext;
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

    // Hanya admin desa yang melihat menu UMKM di sidebar. Super admin masuk lewat
    // aksi "Kelola › UMKM" di tabel Desa (DesaContext) — halaman tetap dapat diakses
    // (lihat canAccess), tapi TIDAK ditampilkan di sidebar agar tetap bersih.
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isDesaAdmin();
    }

    /**
     * desa_admin selalu boleh. super admin hanya saat sedang mengelola sebuah desa
     * (DesaContext, lewat aksi "Kelola › UMKM"); akses langsung tanpa konteks ditolak.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            return true;
        }

        return $user?->isSuperAdmin() && DesaContext::id() !== null;
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
                // Lapak resmi saja — profil pengajuan (menunggu/ditolak) dikelola
                // lewat menu "Pengajuan UMKM", jangan tampil ganda di sini.
                ->whereNull('status_pengajuan')
                ->with(['desa', 'owner'])
                ->withoutGlobalScopes([SoftDeletingScope::class])
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getRecordRouteBindingEloquentQuery()
                // Profil pengajuan tak bisa dibuka/diedit dari resource ini (URL
                // langsung sekalipun) — keputusannya lewat menu "Pengajuan UMKM".
                ->whereNull('status_pengajuan')
                ->withoutGlobalScopes([SoftDeletingScope::class])
        );
    }

    /**
     * Ter-scope ke desa yang sedang dikelola: desa_admin → desanya; super admin →
     * desa konteks (DesaContext). Tanpa konteks, super admin tak bisa akses (canAccess).
     */
    protected static function scopeToActor(Builder $query): Builder
    {
        $desaId = auth()->user()?->managedDesaId();

        if ($desaId !== null) {
            $query->forDesa($desaId);
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
