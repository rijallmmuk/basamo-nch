<?php

namespace App\Filament\Resources\UmkmProducts;

use App\Enums\UmkmProductStatus;
use App\Filament\Resources\UmkmProducts\Pages\ListUmkmProducts;
use App\Filament\Resources\UmkmProducts\Tables\UmkmProductsTable;
use App\Models\UmkmProduct;
use App\Support\DesaContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Antrian verifikasi produk lintas usaha (global). desa_admin hanya melihat
 * produk di desanya; super_admin melihat semua. Hanya daftar + aksi
 * setujui/tolak — produk dibuat pemilik di portal.
 */
class UmkmProductResource extends Resource
{
    protected static ?string $model = UmkmProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Tampil di sidebar untuk admin desa; untuk super admin hanya saat sedang
    // mengelola sebuah desa (masuk lewat aksi "Kelola › UMKM" di menu Desa).
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * desa_admin selalu boleh. super admin hanya saat sedang mengelola sebuah desa
     * (DesaContext); akses langsung tanpa konteks ditolak.
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
        return 'Verifikasi Produk';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Verifikasi Produk';
    }

    /** Badge navigasi = jumlah produk menunggu verifikasi (ter-scope aktor). */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', UmkmProductStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return UmkmProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getEloquentQuery()
                // Produk bawaan PENGAJUAN tidak antre di sini — ia disetujui sepaket
                // lewat menu "Pengajuan UMKM" (cegah antrean ganda / setuju separuh).
                ->whereHas('umkmProfile', fn (Builder $q) => $q->whereNull('status_pengajuan'))
                ->with(['umkmProfile.desa', 'umkmProfile.owner', 'category'])
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
            $query->whereHas('umkmProfile', fn (Builder $q) => $q->where('desa_id', $desaId));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUmkmProducts::route('/'),
        ];
    }
}
