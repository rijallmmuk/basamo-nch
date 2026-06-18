<?php

namespace App\Filament\Resources\Nagaris;

use App\Filament\Resources\Nagaris\Pages\CreateNagari;
use App\Filament\Resources\Nagaris\Pages\EditNagari;
use App\Filament\Resources\Nagaris\Pages\ListNagaris;
use App\Filament\Resources\Nagaris\Schemas\NagariForm;
use App\Filament\Resources\Nagaris\Tables\NagarisTable;
use App\Models\Nagari;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NagariResource extends Resource
{
    protected static ?string $model = Nagari::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getModelLabel(): string
    {
        return 'Nagari';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Nagari';
    }

    public static function form(Schema $schema): Schema
    {
        return NagariForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NagarisTable::configure($table);
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
     * Cegah orphan: nagari yang masih memiliki pengguna/modul tidak boleh dihapus.
     * `$includeTrashed` dipakai untuk force delete (hard) yang akan men-null-kan FK
     * bahkan untuk pengguna/modul yang sudah di-soft-delete.
     */
    public static function guardAgainstDependents(Nagari $record, Action $action, bool $includeTrashed = false): void
    {
        $users = $record->users();
        $modules = $record->modules();

        if ($includeTrashed) {
            $users->withTrashed();
            $modules->withTrashed();
        }

        if ($users->exists() || $modules->exists()) {
            Notification::make()
                ->title('Nagari tidak bisa dihapus')
                ->body('Masih ada pengguna atau modul yang terhubung. Pindahkan atau hapus terlebih dahulu, atau ubah status nagari menjadi Nonaktif.')
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
            'index' => ListNagaris::route('/'),
            'create' => CreateNagari::route('/create'),
            'edit' => EditNagari::route('/{record}/edit'),
        ];
    }
}
