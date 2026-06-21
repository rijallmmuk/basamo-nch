<?php

namespace App\Filament\Resources\UmkmProducts;

use App\Enums\UmkmProductStatus;
use App\Filament\Resources\UmkmProducts\Pages\ListUmkmProducts;
use App\Filament\Resources\UmkmProducts\Tables\UmkmProductsTable;
use App\Models\UmkmProduct;
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

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
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
            parent::getEloquentQuery()->with(['umkmProfile.desa', 'umkmProfile.owner'])
        );
    }

    /** desa_admin hanya produk di desanya; super_admin melihat semua. */
    protected static function scopeToActor(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            $query->whereHas('umkmProfile', fn (Builder $q) => $q->where('desa_id', $user->desa_id));
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
