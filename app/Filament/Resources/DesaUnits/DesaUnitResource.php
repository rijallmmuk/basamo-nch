<?php

namespace App\Filament\Resources\DesaUnits;

use App\Filament\Resources\DesaUnits\Pages\CreateDesaUnit;
use App\Filament\Resources\DesaUnits\Pages\EditDesaUnit;
use App\Filament\Resources\DesaUnits\Pages\ListDesaUnits;
use App\Filament\Resources\DesaUnits\Schemas\DesaUnitForm;
use App\Filament\Resources\DesaUnits\Tables\DesaUnitsTable;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Support\DesaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DesaUnitResource extends Resource
{
    protected static ?string $model = DesaUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    // Tampil di sidebar untuk admin desa; untuk super admin hanya saat sedang
    // mengelola sebuah desa (masuk lewat aksi "Kelola Wilayah" di menu Desa).
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * desa_admin selalu boleh. super admin hanya saat sedang mengelola sebuah desa
     * (masuk lewat aksi "Kelola Wilayah"); akses langsung tanpa konteks → ditolak.
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
        return static::subUnitLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::subUnitLabel();
    }

    /**
     * Sebutan menu/label mengikuti jenis sub-unit desa yang sedang dikelola
     * (Jorong/Korong/Dusun, dll): desa_admin → desanya; super admin → desa konteks.
     * Tanpa konteks (super admin lintas-desa) → istilah umum "Wilayah".
     */
    protected static function subUnitLabel(): string
    {
        $desaId = auth()->user()?->managedDesaId();

        return ($desaId ? Desa::find($desaId)?->jenisSubUnit?->nama : null) ?: 'Wilayah';
    }

    public static function form(Schema $schema): Schema
    {
        return DesaUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesaUnitsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToDesa(
            parent::getEloquentQuery()
                ->with('desa')
                // Hitung khusus warga (bukan akun admin) — dipakai kolom & guard hapus.
                ->withCount('warga')
        );
    }

    /**
     * Cegah hapus sub-unit yang masih dihuni warga: FK `desa_unit_id` ber-ON DELETE
     * SET NULL, jadi hapus permanen akan melucuti alamat warga/penduduk secara senyap;
     * soft delete menyisakan id menggantung. Konsisten dgn DesaResource::guardAgainstDependents.
     */
    public static function guardAgainstWarga(DesaUnit $record, Action $action, bool $includeTrashed = false): void
    {
        $warga = $record->warga();

        if ($includeTrashed) {
            $warga->withTrashed();
        }

        if ($warga->exists()) {
            $sebutan = $record->desa?->jenisSubUnit?->nama ?: 'Wilayah';

            Notification::make()
                ->title("{$sebutan} tidak bisa dihapus")
                ->body("Masih ada warga yang beralamat di sini. Pindahkan warga ke {$sebutan} lain lebih dulu (lewat menu Warga), baru hapus.")
                ->danger()
                ->send();

            $action->halt();
        }
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToDesa(parent::getRecordRouteBindingEloquentQuery());
    }

    /**
     * Ter-scope ke desa yang sedang dikelola: desa_admin → desanya; super admin →
     * desa yang ia kelola lewat aksi "Kelola Wilayah" (DesaContext). Tanpa konteks,
     * super admin tak bisa mengakses resource ini (lihat canAccess).
     */
    protected static function scopeToDesa(Builder $query): Builder
    {
        $desaId = auth()->user()?->managedDesaId();

        if ($desaId !== null) {
            $query->forDesa($desaId);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesaUnits::route('/'),
            'create' => CreateDesaUnit::route('/create'),
            'edit' => EditDesaUnit::route('/{record}/edit'),
        ];
    }
}
