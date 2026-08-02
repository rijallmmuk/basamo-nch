<?php

namespace App\Filament\Resources\SlcRekaps;

use App\Filament\Resources\SlcRekaps\Pages\ListSlcRekaps;
use App\Filament\Resources\SlcRekaps\Pages\ViewSlcRekap;
use App\Filament\Resources\SlcRekaps\Tables\SlcRekapsTable;
use App\Models\User;
use App\Services\SlcRekapService;
use App\Support\NagariContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rekap belajar warga (baca saja — tanpa create/edit/delete): per-warga, modul mana
 * yang selesai, Pre-test/Evaluasi yang dikerjakan beserta nilainya, dan partisipasi diskusi
 * per sumber. Resource TERPISAH dari PendudukResource (Warga) yang mengelola identitas —
 * ini murni pemantauan progres (model sama `User`, role warga, beda resource). Grup
 * "SLC". Panel `/panel` khusus superadmin — dilihat lewat konteks nagari (NagariContext).
 */
class SlcRekapResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'SLC';
    }

    public static function getNavigationLabel(): string
    {
        return 'Rekap Belajar Warga';
    }

    public static function getModelLabel(): string
    {
        return 'Rekap Belajar Warga';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rekap Belajar Warga';
    }

    // Selalu tampil — pola sama Warga/UMKM (2026-07-14): tanpa konteks otomatis
    // ke nagari pertama.
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])
            && parent::canAccess();
    }

    public static function table(Table $table): Table
    {
        return SlcRekapsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $nagariId = $user?->managedNagariId(NagariContext::LMS_REKAP);
        $rekap = app(SlcRekapService::class);
        $moduleIds = fn (): Builder => $rekap->modulesFor($user, $nagariId)
            ->select('modules.id');
        $evaluasiIds = fn (): Builder => $rekap->evaluasisFor($user, $nagariId)
            ->select('evaluasis.id');
        $discussionIds = fn (): Builder => $rekap->discussionsFor($user, $nagariId)
            ->select('discussions.id');

        return parent::getEloquentQuery()
            ->role('warga')
            ->where('status', 'active')
            ->when($nagariId, fn (Builder $q) => $q->where('nagari_id', $nagariId))
            ->when($user?->isPengajar(), fn (Builder $query) => $query->where(
                fn (Builder $participation) => $participation
                    ->whereHas('moduleProgress', fn (Builder $progress) => $progress
                        ->whereIn('module_id', $moduleIds()))
                    ->orWhereHas('evaluasiPercobaans', fn (Builder $attempts) => $attempts
                        ->whereIn('evaluasi_id', $evaluasiIds()))
                    ->orWhereHas('discussions', fn (Builder $discussions) => $discussions
                        ->whereIn('discussions.id', $discussionIds())),
            ))
            // Semua relasi yang dipakai kolom ringkasan di-eager-load sekali agar
            // tabel tidak memicu query tambahan untuk setiap warga.
            ->with([
                // Scope progres ke modul yang KINI menyasar nagari (pengajar: yang ia ampu).
                // Tanpa ini, melepas nagari dari targeting membuat "selesai" > "total".
                'moduleProgress' => fn ($progress) => $progress
                    ->whereIn('module_id', $moduleIds()),
                'moduleProgress.module',
                'evaluasiPercobaans' => fn ($attempts) => $attempts
                    ->whereIn('evaluasi_id', $evaluasiIds()),
                'evaluasiPercobaans.evaluasi.module',
                'discussions' => fn ($discussions) => $discussions
                    ->whereIn('discussions.id', $discussionIds()),
                'discussions.module',
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlcRekaps::route('/'),
            'view' => ViewSlcRekap::route('/{record}'),
        ];
    }
}
