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
 * super_admin: semua desa. desa_admin: hanya diskusi warga desanya (scope query + Policy).
 */
class DiscussionResource extends Resource
{
    protected static ?string $model = Discussion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'LMS';
    }

    public static function getModelLabel(): string
    {
        return 'Diskusi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Diskusi';
    }

    public static function table(Table $table): Table
    {
        return DiscussionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.desa', 'module'])
            ->withCount('replies')
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        // desa_admin hanya melihat diskusi yang ditulis warga desanya.
        if ($user?->isDesaAdmin()) {
            $query->whereHas('user', fn ($q) => $q->where('desa_id', $user->desa_id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscussions::route('/'),
        ];
    }
}
