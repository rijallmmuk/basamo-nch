<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\DesaContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    // Menu "Warga" = entitas inti admin desa, tampil di tingkat atas navigasi.
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    // Tampil di sidebar untuk admin desa; untuk super admin hanya saat sedang
    // mengelola sebuah desa (masuk lewat aksi "Kelola Warga" di menu Desa).
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getModelLabel(): string
    {
        return 'Warga';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Warga';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getEloquentQuery()
                // desaUnit + penduduk (dengan lookup) untuk kolom demografi yang bisa di-toggle —
                // ter-eager-load per halaman (kena paginasi) → aman dari N+1.
                ->with(['desa', 'desaUnit', 'penduduk.agama', 'penduduk.statusPerkawinan', 'penduduk.pekerjaan'])
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
     * Resource khusus WARGA: hanya akun ber-peran `warga`. Ter-scope ke desa yang
     * sedang dikelola: desa_admin → desanya; super admin → desa yang ia kelola lewat
     * aksi "Kelola Warga". Dipakai listing & route-model binding.
     */
    protected static function scopeToActor(Builder $query): Builder
    {
        $query->where('role', 'warga');

        $desaId = auth()->user()?->managedDesaId();

        if ($desaId !== null) {
            $query->forDesa($desaId);
        }

        return $query;
    }

    /**
     * desa_admin selalu boleh. super admin hanya saat sedang mengelola sebuah desa
     * (masuk lewat aksi "Kelola Warga"); akses langsung tanpa konteks → ditolak.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            return true;
        }

        return $user?->isSuperAdmin() && DesaContext::id() !== null;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
