<?php

namespace App\Filament\Resources\Pelatihans;

use App\Enums\StatusPelatihan;
use App\Filament\Resources\Pelatihans\Pages\CreatePelatihan;
use App\Filament\Resources\Pelatihans\Pages\EditPelatihan;
use App\Filament\Resources\Pelatihans\Pages\ListPelatihans;
use App\Filament\Resources\Pelatihans\Pages\ViewPelatihan;
use App\Filament\Resources\Pelatihans\RelationManagers\ModulesRelationManager;
use App\Filament\Resources\Pelatihans\Schemas\PelatihanForm;
use App\Filament\Resources\Pelatihans\Schemas\PelatihanInfolist;
use App\Filament\Resources\Pelatihans\Tables\PelatihansTable;
use App\Models\Pelatihan;
use App\Notifications\PelatihanDibuka;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Notification;

/**
 * Pelatihan = satu pelaksanaan kegiatan, entitas tertinggi SLC di atas Modul.
 * superadmin & dpmd: semua (dpmd read-only via Gate::before). operator: pelaksanaan
 * yang menyasar nagarinya. pengajar: yang ia buat atau yang ditugaskan kepadanya.
 */
class PelatihanResource extends Resource
{
    protected static ?string $model = Pelatihan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Pelatihan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pelatihan';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'SLC';
    }

    /** Judul rekaman dirakit dari tema + sasaran (tak ada kolom nama). */
    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->namaTampil();
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])
            && parent::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['tema', 'nagaris', 'media'])
            ->when($user, fn (Builder $query) => $query->visibleTo($user));
    }

    public static function form(Schema $schema): Schema
    {
        return PelatihanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PelatihanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PelatihansTable::configure($table);
    }

    /**
     * Ubah status pelaksanaan. Saat BARU dibuka (belum pernah terbuka → terbuka),
     * umumkan ke warga nagari sasaran. Mengunci kembali tidak memberi tahu siapa pun.
     */
    public static function setStatus(Pelatihan $pelatihan, StatusPelatihan $status): void
    {
        if ($status === StatusPelatihan::Terbuka && ! $pelatihan->isReady()) {
            throw new \DomainException('Pelatihan belum memiliki sasaran nagari atau modul yang berisi materi.');
        }

        $sebelumnya = $pelatihan->status;
        $pelatihan->update(['status' => $status]);

        if ($sebelumnya !== StatusPelatihan::Terbuka && $status === StatusPelatihan::Terbuka) {
            $pelatihan->wargaSasaran()
                ->chunkById(500, fn ($warga) => Notification::send($warga, new PelatihanDibuka($pelatihan)));
        }
    }

    public static function getRelations(): array
    {
        return [
            ModulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPelatihans::route('/'),
            'create' => CreatePelatihan::route('/create'),
            'view' => ViewPelatihan::route('/{record}'),
            'edit' => EditPelatihan::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['tema', 'nagaris', 'media'])
            ->when($user, fn (Builder $query) => $query->visibleTo($user))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
