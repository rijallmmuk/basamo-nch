<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
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

    // Menu "Warga" = entitas inti, tampil di tingkat atas navigasi (bukan grup Pengaturan).
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return null;
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

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
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
     * Resource khusus WARGA: hanya akun ber-peran `warga` yang tampil/teredit di sini
     * (akun admin dikelola lewat alur lain). desa_admin dibatasi ke desanya sendiri;
     * super_admin melihat warga semua desa. Dipakai listing & route-model binding.
     */
    protected static function scopeToActor(Builder $query): Builder
    {
        $query->where('role', 'warga');

        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            $query->forDesa($user->desa_id);
        }

        return $query;
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
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
