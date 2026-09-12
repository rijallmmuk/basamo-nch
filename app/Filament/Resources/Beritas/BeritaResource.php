<?php

namespace App\Filament\Resources\Beritas;

use App\Filament\Resources\Beritas\Pages\CreateBerita;
use App\Filament\Resources\Beritas\Pages\EditBerita;
use App\Filament\Resources\Beritas\Pages\ListBeritas;
use App\Filament\Resources\Beritas\Pages\ViewBerita;
use App\Filament\Resources\Beritas\Schemas\BeritaForm;
use App\Filament\Resources\Beritas\Schemas\BeritaInfolist;
use App\Filament\Resources\Beritas\Tables\BeritasTable;
use App\Models\Berita;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BeritaResource extends Resource
{
    protected static ?string $model = Berita::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Berita & Pengumuman';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Berita & Pengumuman';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kabar Nagari';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Publikasi';
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->judul;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd'])
            && parent::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['nagari', 'nagaris', 'creator', 'media'])
            ->when($user?->isOperator(), function (Builder $query) use ($user) {
                if ($user->nagari_id === null) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function (Builder $q) use ($user) {
                    $q->where('nagari_id', $user->nagari_id)
                        ->orWhere('semua_nagari', true)
                        ->orWhereHas('nagaris', fn (Builder $sub) => $sub->where('nagaris.id', $user->nagari_id));
                });
            });
    }

    public static function form(Schema $schema): Schema
    {
        return BeritaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BeritaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BeritasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBeritas::route('/'),
            'create' => CreateBerita::route('/create'),
            'view' => ViewBerita::route('/{record}'),
            'edit' => EditBerita::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['nagari', 'nagaris', 'media', 'creator'])
            ->when($user?->isOperator(), function (Builder $query) use ($user) {
                if ($user->nagari_id === null) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function (Builder $q) use ($user) {
                    $q->where('nagari_id', $user->nagari_id)
                        ->orWhere('semua_nagari', true)
                        ->orWhereHas('nagaris', fn (Builder $sub) => $sub->where('nagaris.id', $user->nagari_id));
                });
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
