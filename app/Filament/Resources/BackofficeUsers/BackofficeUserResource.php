<?php

namespace App\Filament\Resources\BackofficeUsers;

use App\Filament\Resources\BackofficeUsers\Pages\CreateBackofficeUser;
use App\Filament\Resources\BackofficeUsers\Pages\EditBackofficeUser;
use App\Filament\Resources\BackofficeUsers\Pages\ListBackofficeUsers;
use App\Filament\Resources\BackofficeUsers\Pages\ViewBackofficeUser;
use App\Filament\Resources\BackofficeUsers\Schemas\BackofficeUserForm;
use App\Filament\Resources\BackofficeUsers\Schemas\BackofficeUserInfolist;
use App\Filament\Resources\BackofficeUsers\Tables\BackofficeUsersTable;
use App\Models\Pelatihan;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BackofficeUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Sistem';
    }

    public static function getNavigationLabel(): string
    {
        return 'Manajemen Akun';
    }

    public static function getModelLabel(): string
    {
        return 'Akun';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Manajemen Akun';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin()
            && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return BackofficeUserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BackofficeUserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BackofficeUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * T-3: pengajar = akun INDIVIDU. Saat dihapus/dinonaktifkan, program & modul
     * buatannya tak ikut terhapus, tapi "yatim" ke superadmin (pengajar lain/operator
     * tak lagi berwenang; created_by dinolkan saat hapus permanen). Peringatan ini
     * mengingatkan admin untuk menugaskan pengajar pengganti. Null bila tak ada konten.
     */
    public static function turnoverWarning(User $record): ?string
    {
        if (! $record->hasRole('pengajar')) {
            return null;
        }

        $diampu = $record->pelatihansDiajar()->count();
        $dibuat = Pelatihan::query()->where('created_by', $record->getKey())->count();

        if ($diampu === 0 && $dibuat === 0) {
            return null;
        }

        return "Pengajar ini menangani {$diampu} program dan membuat {$dibuat} program. "
            .'Setelahnya, program & modul tersebut hanya dapat dikelola superadmin. '
            .'Tugaskan pengajar pengganti lewat form program bila perlu.';
    }

    /** Peran yang dikelola dari menu ini. */
    public const PERAN = ['superadmin', 'pengajar', 'dpmd'];

    /**
     * Hanya akun panel lintas nagari. Operator TIDAK didaftar di sini karena
     * dibuat dan diurus lewat menu Nagari, sedangkan warga pemilik UMKM diurus
     * lewat Data Warga.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['roles', 'penduduk'])
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', self::PERAN))
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackofficeUsers::route('/'),
            'create' => CreateBackofficeUser::route('/create'),
            'view' => ViewBackofficeUser::route('/{record}'),
            'edit' => EditBackofficeUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', self::PERAN))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
