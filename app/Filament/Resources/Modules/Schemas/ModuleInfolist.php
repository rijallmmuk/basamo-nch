<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Models\Module;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class ModuleInfolist
{
    private static function cakupan(Module $record): string
    {
        if ($record->pelatihan?->semua_nagari) {
            return 'Semua nagari';
        }

        $nagaris = $record->pelatihan?->nagaris ?? collect();

        return match (true) {
            $nagaris->isEmpty() => 'Belum ada',
            $nagaris->count() === 1 => (string) $nagaris->first()->nama,
            default => $nagaris->pluck('nama')->join(', '),
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            /* Susunan sama dengan halaman pelatihan: ringkasan singkat yang selalu
               terbuka, sisanya terlipat agar tabel Halaman Materi cepat dicapai.
               Judul modul tidak diulang karena sudah menjadi judul halaman. */
            Section::make('Ringkasan')
                ->icon(Heroicon::OutlinedBookOpen)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    // Modul tidak punya saklar terbit: yang menentukan adalah ada
                    // tidaknya materi dan status pelatihannya. Tanpa baris ini,
                    // pengajar tak punya cara tahu mengapa modulnya belum terlihat.
                    TextEntry::make('kesiapan')
                        ->label('Status Tampil ke Warga')
                        ->state(fn (Module $record): string => match (true) {
                            ! $record->isReady() => 'Belum tampil',
                            ! ($record->pelatihan?->dapatDimasuki() ?? false) => 'Menunggu pelatihan dibuka',
                            default => 'Sudah tampil',
                        })
                        ->badge()
                        ->color(fn (Module $record): string => match (true) {
                            ! $record->isReady() => 'danger',
                            ! ($record->pelatihan?->dapatDimasuki() ?? false) => 'warning',
                            default => 'success',
                        })
                        ->helperText(fn (Module $record): ?string => match (true) {
                            ! $record->isReady() => 'Tambahkan minimal satu materi agar modul ini terlihat warga.',
                            ! ($record->pelatihan?->dapatDimasuki() ?? false) => 'Buka pelatihannya lewat tombol "Buka untuk Warga" pada halaman pelatihan.',
                            default => null,
                        }),

                    TextEntry::make('materis_count')
                        ->label('Jumlah Materi')
                        ->state(fn (Module $record): string => ($record->materis_count ?? $record->materis()->count()).' Materi')
                        ->icon('heroicon-m-document-text')
                        ->color('info')
                        ->weight(FontWeight::Bold),

                    TextEntry::make('pelatihan_nama')
                        ->label('Pelatihan')
                        ->state(fn (Module $record): string => $record->pelatihan?->namaTampil() ?? 'Tanpa pelatihan')
                        ->badge()
                        ->color('info')
                        ->icon('heroicon-m-academic-cap'),
                ]),

            Section::make('Rincian & Sampul')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->columns(3)
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                // Posisi lipatan diingat per pengguna; lihat catatan yang sama pada
                // PelatihanInfolist.
                ->persistCollapsed()
                ->schema([
                    Group::make()->schema([
                        TextEntry::make('cakupan')
                            ->label('Cakupan Nagari')
                            ->state(fn (Module $record): string => self::cakupan($record))
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-map-pin'),

                        TextEntry::make('prerequisite_judul')
                            ->label('Prasyarat Modul')
                            ->state(fn (Module $record): string => $record->prerequisite?->judul ?? 'Tidak ada prasyarat')
                            ->icon('heroicon-m-link'),

                        TextEntry::make('creator.name')
                            ->label('Dibuat oleh')
                            ->placeholder('Tidak diketahui')
                            ->icon('heroicon-m-user')
                            ->hidden(fn (): bool => auth()->user()?->isPengajar() ?? false),

                        ViewEntry::make('deskripsi')
                            ->label('Deskripsi Modul')
                            ->view('filament.infolists.components.deskripsi-ringkas')
                            ->columnSpanFull(),
                    ])->columns(2)->columnSpan(2),

                    Group::make()->schema([
                        ViewEntry::make('cover_view')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.compact-cover'),
                    ])->columnSpan(1),
                ]),
        ]);
    }
}
