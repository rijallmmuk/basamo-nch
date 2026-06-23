<?php

namespace App\Filament\Resources\DesaUnits;

use App\Filament\Resources\DesaUnits\Pages\CreateDesaUnit;
use App\Filament\Resources\DesaUnits\Pages\EditDesaUnit;
use App\Filament\Resources\DesaUnits\Pages\ListDesaUnits;
use App\Filament\Resources\DesaUnits\Schemas\DesaUnitForm;
use App\Filament\Resources\DesaUnits\Tables\DesaUnitsTable;
use App\Models\DesaUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DesaUnitResource extends Resource
{
    protected static ?string $model = DesaUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
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
     * Sebutan menu/label mengikuti jenis sub-unit yang diatur per desa (Jorong/Korong/
     * Dusun, dll). super_admin lintas-desa → istilah umum "Wilayah".
     */
    protected static function subUnitLabel(): string
    {
        $actor = auth()->user();

        return $actor?->isDesaAdmin()
            ? ($actor->desa?->jenisSubUnit?->nama ?: 'Wilayah')
            : 'Wilayah';
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
                ->withCount('users')
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToDesa(parent::getRecordRouteBindingEloquentQuery());
    }

    /**
     * desa_admin hanya mengelola wilayah desanya sendiri.
     * super_admin melihat semua (dilewatkan via Gate::before untuk policy).
     */
    protected static function scopeToDesa(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            $query->forDesa($user->desa_id);
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
