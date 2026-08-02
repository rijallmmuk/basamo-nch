<?php

namespace App\Filament\Resources\Penduduks;

use App\Filament\Resources\Penduduks\Pages\CreatePenduduk;
use App\Filament\Resources\Penduduks\Pages\EditPenduduk;
use App\Filament\Resources\Penduduks\Pages\ListPenduduks;
use App\Filament\Resources\Penduduks\Pages\ViewPenduduk;
use App\Filament\Resources\Penduduks\Schemas\PendudukForm;
use App\Filament\Resources\Penduduks\Schemas\PendudukInfolist;
use App\Filament\Resources\Penduduks\Tables\PenduduksTable;
use App\Models\Discussion;
use App\Models\Penduduk;
use App\Support\NagariContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendudukResource extends Resource
{
    protected static ?string $model = Penduduk::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $slug = 'warga';

    public static function getNavigationGroup(): ?string
    {
        return 'Nagari';
    }

    public static function getModelLabel(): string
    {
        return 'Warga';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Warga';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd'])
            && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return PendudukForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PendudukInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenduduksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getEloquentQuery()
                ->with([
                    'nagari',
                    'agama',
                    'pendidikan',
                    'statusPerkawinan',
                    'pekerjaan',
                    'user.roles',
                    'user.umkmProfile',
                ])
                ->withoutGlobalScopes([SoftDeletingScope::class])
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::scopeToActor(
            parent::getRecordRouteBindingEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class])
        );
    }

    protected static function scopeToActor(Builder $query): Builder
    {
        $nagariId = auth()->user()?->managedNagariId(NagariContext::WARGA);

        return $query->when($nagariId !== null, fn (Builder $builder) => $builder->forNagari($nagariId));
    }

    /**
     * Cegah kehilangan data pihak ketiga: hapus permanen warga akan meng-cascade
     * diskusi (`discussions.user_id` → `parent_id`), sehingga thread yang ia mulai
     * DAN balasan warga LAIN ikut terhapus. Blokir bila thread-nya sudah dibalas
     * orang lain; minta moderasi diskusi itu dulu.
     */
    public static function guardAgainstThirdPartyDiscussions(Penduduk $record, Action $action): void
    {
        // Force-delete terjadi setelah warga diarsipkan → akunnya sudah soft-deleted,
        // jadi WAJIB withTrashed agar tidak lolos gate secara palsu.
        $userId = $record->user()->withTrashed()->value('id');

        if ($userId === null) {
            return;
        }

        $adaBalasanOrangLain = Discussion::query()
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->whereHas('replies', fn (Builder $replies) => $replies->where('user_id', '!=', $userId))
            ->exists();

        if ($adaBalasanOrangLain) {
            Notification::make()
                ->title('Warga tidak bisa dihapus permanen')
                ->body('Warga ini memulai diskusi yang sudah dibalas warga lain. Menghapus permanen akan ikut menghapus balasan mereka. Moderasi atau selesaikan diskusi tersebut lebih dulu, atau cukup arsipkan warga ini.')
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
            'index' => ListPenduduks::route('/'),
            'create' => CreatePenduduk::route('/create'),
            'view' => ViewPenduduk::route('/{record}'),
            'edit' => EditPenduduk::route('/{record}/edit'),
        ];
    }
}
