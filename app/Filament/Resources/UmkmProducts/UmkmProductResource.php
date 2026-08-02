<?php

namespace App\Filament\Resources\UmkmProducts;

use App\Filament\Resources\UmkmProducts\Pages\CreateUmkmProduct;
use App\Filament\Resources\UmkmProducts\Pages\ListUmkmProducts;
use App\Filament\Resources\UmkmProducts\Pages\ViewUmkmProduct;
use App\Filament\Resources\UmkmProducts\Schemas\UmkmProductInfolist;
use App\Filament\Resources\UmkmProducts\Tables\UmkmProductsTable;
use App\Models\UmkmProduct;
use App\Support\NagariContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Katalog SEMUA produk UMKM + filter kategori untuk MODERASI admin (ubah/hapus).
 * Produk tidak punya status terbit maupun arsip: begitu dibuat ia tampil, dan
 * menghapusnya bersifat permanen. Dilihat lewat konteks nagari (NagariContext).
 */
class UmkmProductResource extends Resource
{
    protected static ?string $model = UmkmProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Selalu tampil di sidebar (2026-07-14, keputusan user: "kuasa penuh" superadmin) —
    // klik langsung tanpa konteks otomatis diarahkan ke nagari pertama
    // (lihat ListUmkmProducts::mount / NagariContext::ensureDefault), bukan buntu 403.
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        // superadmin (lintas-nagari) + operator (nagarinya, via getEloquentQuery
        // managedNagariId) + dpmd (read-only). Pemilik baru boleh masuk setelah
        // profil usahanya ada, supaya tidak ada tombol Tambah Produk yang mustahil
        // disimpan karena belum memiliki lapak.
        return (bool) (auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd'])
            || (auth()->user()?->usesUmkmSelfService()
                && auth()->user()?->umkmProfile()->exists()))
            && parent::canAccess();
    }

    public static function getModelLabel(): string
    {
        return static::isSelfService() ? 'Produk Saya' : 'Produk UMKM';
    }

    public static function getPluralModelLabel(): string
    {
        return static::isSelfService() ? 'Produk Saya' : 'Produk UMKM';
    }

    public static function getNavigationLabel(): string
    {
        return static::isSelfService() ? 'Kelola Produk' : 'Produk UMKM';
    }

    public static function infolist(Schema $schema): Schema
    {
        return UmkmProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UmkmProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getEloquentQuery()
                ->with(['umkmProfile.nagari', 'umkmProfile.owner', 'category'])
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
            return $query->whereHas(
                'umkmProfile',
                fn (Builder $profiles) => $profiles->where('user_id', $user->getKey()),
            );
        }

        $nagariId = $user?->managedNagariId(NagariContext::UMKM_PRODUK);

        if ($nagariId !== null) {
            $query->whereHas('umkmProfile', fn (Builder $q) => $q->where('nagari_id', $nagariId));
        }

        return $query;
    }

    public static function isSelfService(): bool
    {
        return auth()->user()?->usesUmkmSelfService() ?? false;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToActor(parent::getRecordRouteBindingEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUmkmProducts::route('/'),
            // Tambah produk berupa halaman penuh: formulirnya dua bagian plus
            // pengunggah lima foto, terlalu sesak di dalam modal. Ubah tetap modal.
            'create' => CreateUmkmProduct::route('/tambah'),
            'view' => ViewUmkmProduct::route('/{record}'),
        ];
    }
}
