<?php

namespace App\Filament\Resources\Wilayahs;

use App\Filament\Resources\Wilayahs\Pages\CreateWilayah;
use App\Filament\Resources\Wilayahs\Pages\EditWilayah;
use App\Filament\Resources\Wilayahs\Pages\ListWilayahs;
use App\Filament\Resources\Wilayahs\Schemas\WilayahForm;
use App\Filament\Resources\Wilayahs\Tables\WilayahsTable;
use App\Models\Wilayah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WilayahResource extends Resource
{
    protected static ?string $model = Wilayah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getModelLabel(): string
    {
        return 'Wilayah';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Wilayah';
    }

    public static function form(Schema $schema): Schema
    {
        return WilayahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WilayahsTable::configure($table);
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
            $query->where('desa_id', $user->desa_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWilayahs::route('/'),
            'create' => CreateWilayah::route('/create'),
            'edit' => EditWilayah::route('/{record}/edit'),
        ];
    }
}
