<?php

namespace App\Filament\Resources\SdgAchievements;

use App\Filament\Resources\SdgAchievements\Pages\ListSdgAchievements;
use App\Filament\Resources\SdgAchievements\Pages\ViewSdgAchievement;
use App\Models\SdgAchievement;
use App\Models\SdgTarget;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Capaian SDGs Desa per Poin (potret berjalan, tanpa tahun) — skor ditarik dari
 * API Kemendesa via job terjadwal (lihat SdgKemendesaService/SdgsRefreshKemendesa),
 * BUKAN input manual. Halaman ini murni viewer baca-saja + panduan sasaran/
 * indikator referensi. Pemilihan poin lewat grid 18 kartu ber-ikon (halaman
 * List). Panel `/panel` khusus superadmin (Fase 7 Cutover) — selalu lintas-nagari.
 */
class SdgAchievementResource extends Resource
{
    protected static ?string $model = SdgAchievement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAsiaAustralia;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Status Desa';
    }

    public static function getModelLabel(): string
    {
        return 'Capaian SDGs';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Capaian SDGs';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('poin_header')
                ->hiddenLabel()
                ->columnSpanFull()
                ->html()
                ->state(function (SdgAchievement $record): HtmlString {
                    $goal = $record->goal?->loadMissing('pillar');

                    if (! $goal) {
                        return new HtmlString('');
                    }

                    return new HtmlString(
                        '<div class="flex items-center gap-4">'
                        .'<img src="'.e($goal->ikonUrl()).'" alt="Poin '.e($goal->nomor).'" class="h-16 w-16 shrink-0 rounded-lg object-cover">'
                        .'<div>'
                        .'<p class="text-lg font-bold leading-6 text-gray-950 dark:text-white">Poin '.e($goal->nomor).' · '.e($goal->nama).'</p>'
                        .'<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Pilar '.e($goal->pillar?->nama).'</p>'
                        .'</div>'
                        .'</div>'
                    );
                }),

            Section::make('Panduan — Sasaran & Indikator')
                ->description('Sasaran yang tercakup poin ini; klik sebuah sasaran untuk melihat indikatornya.')
                ->icon(Heroicon::OutlinedBookOpen)
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('panduan')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->html()
                        ->state(fn (SdgAchievement $record): HtmlString => self::panduanSasaran($record->sdg_goal_id)),
                ]),

            Section::make('Capaian')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('persentase')
                        ->label('Nilai')
                        ->suffix('%')
                        ->numeric(2),

                    TextEntry::make('fetched_at')
                        ->label('Terakhir diperbarui')
                        ->since()
                        ->placeholder('Belum pernah ditarik dari API Kemendesa'),
                ]),
        ]);
    }

    /**
     * Panduan dua tingkat: daftar SASARAN sebuah poin, tiap sasaran berupa
     * <details> yang bisa dibuka untuk melihat daftar INDIKATOR-nya (native
     * HTML — tanpa JS, rapi, progresif).
     */
    private static function panduanSasaran(mixed $goalId): HtmlString
    {
        $sasaran = SdgTarget::query()
            ->where('sdg_goal_id', $goalId)
            ->with('indicators')
            ->orderBy('id')
            ->get();

        $blok = $sasaran->map(function (SdgTarget $t): string {
            $indikator = $t->indicators
                ->map(fn ($i): string => '<li class="flex gap-2"><span class="shrink-0 font-semibold text-gray-500 dark:text-gray-400">'.e($i->kode).'</span><span>'.e($i->deskripsi).'</span></li>')
                ->implode('');

            $isi = $indikator === ''
                ? '<p class="px-4 pb-3 text-sm italic text-gray-400 dark:text-gray-500">Tidak ada indikator terdaftar.</p>'
                : '<ul class="space-y-1.5 border-t border-gray-950/5 px-4 py-3 text-sm text-gray-600 dark:border-white/10 dark:text-gray-400">'.$indikator.'</ul>';

            return '<details class="group overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">'
                .'<summary class="flex cursor-pointer select-none items-start gap-3 px-4 py-3 text-sm">'
                .'<svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-transform group-open:rotate-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>'
                .'<span class="font-semibold text-gray-950 dark:text-white">'.e($t->kode).'</span>'
                .'<span class="min-w-0 flex-1 font-medium text-gray-700 dark:text-gray-300">'.e($t->deskripsi).'</span>'
                .'<span class="shrink-0 rounded-full bg-gray-950/5 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400">'.$t->indicators->count().' indikator</span>'
                .'</summary>'
                .$isi
                .'</details>';
        })->implode('');

        return new HtmlString('<div class="space-y-2">'.$blok.'</div>');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['nagari', 'goal'])
            ->when(
                auth()->user()?->isOperator(),
                fn (Builder $query) => $query->where('nagari_id', auth()->user()?->nagari_id),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdgAchievements::route('/'),
            'view' => ViewSdgAchievement::route('/{record}'),
        ];
    }
}
