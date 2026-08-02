<?php

namespace App\Filament\Resources\Discussions;

use App\Filament\Resources\Discussions\Pages\ListDiscussions;
use App\Filament\Resources\Discussions\Tables\DiscussionsTable;
use App\Models\Discussion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Moderasi diskusi (read-only + aksi pin/hapus/pulihkan). Tanpa create/edit isi.
 * Superadmin dan DPMD melihat semua nagari; operator serta pengajar mengikuti scope.
 */
class DiscussionResource extends Resource
{
    protected static ?string $model = Discussion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'SLC';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);
    }

    public static function getModelLabel(): string
    {
        return 'Forum Diskusi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Forum Diskusi';
    }

    /**
     * Badge navigasi = jumlah diskusi yang BELUM dibaca aktor ini, dihitung dari
     * tabel `discussion_reads` (per pengguna, bukan per waktu kunjungan). Bersih
     * setelah tiap baris ditandai terbaca atau lewat aksi "Tandai Semua Telah Dibaca".
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->whereNull('deleted_at')
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return DiscussionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $userId = $user?->getKey() ?? 0;

        return parent::getEloquentQuery()
            // Hanya PERTANYAAN (top-level) yang dikelola dari tabel ini.
            ->whereNull('parent_id')
            // Balasan (+penulisnya+role+media), modul (+program), serta status baca user di-eager-load.
            ->with([
                'user.nagari',
                'user.roles',
                'user.media',
                'module.pelatihan',
                'reads' => fn ($q) => $user ? $q->where('user_id', $user->id) : $q,
                'replies' => fn ($q) => $q->with(['user.roles', 'user.media'])->oldest(),
            ])
            ->withCount('replies')
            ->withoutGlobalScopes([SoftDeletingScope::class])
            // superadmin & dpmd: semua. operator: diskusi warga nagari sendiri.
            // Pengajar: seluruh thread pada setiap modul yang boleh dikelolanya,
            // siapa pun warga yang memulai diskusi tersebut.
            ->when($user?->isOperator(), fn (Builder $q) => $user->nagari_id === null
                ? $q->whereKey([])
                : $q->whereHas('user', fn (Builder $users) => $users->where('nagari_id', $user->nagari_id)))
            ->when(
                $user && ! $user->isOperator() && $user->isPengajar(),
                fn (Builder $q) => $q->whereHas('module', fn (Builder $modules) => $modules->manageableBy($user)),
            )
            // Belum dibaca didahulukan; masing-masing kelompok tetap kronologis.
            ->orderByRaw('EXISTS (SELECT 1 FROM discussion_reads WHERE discussion_reads.discussion_id = discussions.id AND discussion_reads.user_id = ?) ASC', [$userId])
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscussions::route('/'),
        ];
    }
}
