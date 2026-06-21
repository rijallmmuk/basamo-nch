<?php

namespace App\Filament\Resources\Desas;

use App\Filament\Resources\Desas\Pages\CreateDesa;
use App\Filament\Resources\Desas\Pages\EditDesa;
use App\Filament\Resources\Desas\Pages\ListDesas;
use App\Filament\Resources\Desas\Schemas\DesaForm;
use App\Filament\Resources\Desas\Tables\DesasTable;
use App\Models\Desa;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DesaResource extends Resource
{
    protected static ?string $model = Desa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getModelLabel(): string
    {
        return 'Desa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Desa';
    }

    public static function form(Schema $schema): Schema
    {
        return DesaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['users', 'modules'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Cegah orphan: desa yang masih memiliki pengguna/modul tidak boleh dihapus.
     * `$includeTrashed` dipakai untuk force delete (hard) yang akan men-null-kan FK
     * bahkan untuk pengguna/modul yang sudah di-soft-delete.
     */
    public static function guardAgainstDependents(Desa $record, Action $action, bool $includeTrashed = false): void
    {
        $users = $record->users();
        $modules = $record->modules();

        if ($includeTrashed) {
            $users->withTrashed();
            $modules->withTrashed();
        }

        if ($users->exists() || $modules->exists()) {
            Notification::make()
                ->title('Desa tidak bisa dihapus')
                ->body('Masih ada pengguna atau modul yang terhubung. Pindahkan atau hapus terlebih dahulu, atau ubah status desa menjadi Nonaktif.')
                ->danger()
                ->send();

            $action->halt();
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesas::route('/'),
            'create' => CreateDesa::route('/create'),
            'edit' => EditDesa::route('/{record}/edit'),
        ];
    }
}
