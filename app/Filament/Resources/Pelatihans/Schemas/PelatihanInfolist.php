<?php

namespace App\Filament\Resources\Pelatihans\Schemas;

use App\Models\Pelatihan;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class PelatihanInfolist
{
    /** Yang membuka halaman adalah pemilik pelatihan ini? */
    private static function pemilik(Pelatihan $record): bool
    {
        return $record->creator?->is(auth()->user()) ?? false;
    }

    /** Yang membuka halaman adalah pembantu, bukan pemiliknya? */
    private static function pembantu(Pelatihan $record): bool
    {
        $aktor = auth()->user();

        return $aktor !== null
            && ! self::pemilik($record)
            && $record->pengajars->contains(fn ($pengajar): bool => $pengajar->is($aktor));
    }

    private static function cakupan(Pelatihan $record): string
    {
        if ($record->semua_nagari) {
            return 'Semua nagari';
        }

        $nagaris = $record->nagaris;

        return match (true) {
            $nagaris->isEmpty() => 'Belum ada',
            $nagaris->count() === 1 => (string) $nagaris->first()->nama,
            default => $nagaris->pluck('nama')->join(', '),
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            /* Halaman detail dipecah dua. Yang selalu terbuka hanya tiga hal yang
               dicari operator sekali lihat; sisanya terlipat supaya tabel Daftar Modul,
               tujuan utama halaman ini, langsung terlihat tanpa menggulir. Nama tema
               tidak diulang di sini karena sudah menjadi judul halaman. */
            Section::make('Ringkasan')
                ->icon(Heroicon::OutlinedAcademicCap)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('status')
                        ->label('Akses Warga')
                        ->badge(),

                    TextEntry::make('modules_count')
                        ->label('Jumlah Modul')
                        ->state(fn (Pelatihan $record): int => $record->modules()->count())
                        ->icon('heroicon-m-book-open')
                        ->color('info')
                        ->weight(FontWeight::Bold),

                    TextEntry::make('cakupan')
                        ->label('Sasaran Pelatihan')
                        ->state(fn (Pelatihan $record): string => self::cakupan($record))
                        ->badge()
                        ->color('info'),
                ]),

            Section::make('Rincian & Sampul')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->columns(3)
                ->columnSpanFull()
                ->collapsible()
                ->collapsed()
                // Posisi lipatan diingat per pengguna, jadi operator yang selalu
                // membutuhkan rincian tidak perlu membukanya berulang kali.
                ->persistCollapsed()
                ->schema([
                    Group::make()->schema([
                        // Tiap pihak melihat lawan bicaranya: pemilik melihat siapa yang
                        // ia ajak membantu, pembantu melihat pelatihan ini milik siapa.
                        // Pengawas (superadmin, DPMD, operator) melihat keduanya.
                        TextEntry::make('pengajars')
                            ->label('Kolaborasi')
                            ->state(function (Pelatihan $record): string {
                                $pengajars = $record->pengajars;

                                if ($pengajars->isEmpty()) {
                                    return 'Belum ada';
                                }

                                return $pengajars
                                    ->map(fn ($p): string => $p->lembaga ? "{$p->name} ({$p->lembaga})" : $p->name)
                                    ->join(', ');
                            })
                            ->badge()
                            ->helperText('Membantu mengisi modul, materi, dan evaluasi.')
                            ->hidden(fn (Pelatihan $record): bool => self::pembantu($record)),

                        TextEntry::make('creator.name')
                            ->label('Pemilik')
                            ->placeholder('Tidak diketahui')
                            ->badge()
                            ->color('primary')
                            ->helperText('Hanya pemilik yang dapat mengubah, membuka untuk warga, dan mengatur kolaborasi.')
                            // Pemilik tak perlu diberi tahu bahwa pelatihan ini miliknya.
                            ->hidden(fn (Pelatihan $record): bool => self::pemilik($record)),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i')
                            ->icon('heroicon-m-clock'),

                        /* Tanpa dua baris ini, tab Kehadiran muncul begitu saja tanpa
                           konteks: operator tidak punya cara melihat kapan pertemuannya
                           dan ke ruang mana warga diarahkan. */
                        TextEntry::make('pertemuan_jadwal')
                            ->label('Pertemuan Daring')
                            ->state(fn (Pelatihan $record): string => $record->pertemuan_mulai
                                ? $record->pertemuan_mulai->translatedFormat('d M Y, H:i')
                                    .($record->pertemuan_selesai
                                        ? ' sampai '.$record->pertemuan_selesai->translatedFormat('H:i')
                                        : '')
                                : 'Waktu belum diisi')
                            ->icon('heroicon-m-video-camera')
                            ->visible(fn (Pelatihan $record): bool => $record->punyaPertemuan()),

                        TextEntry::make('pertemuan_url')
                            ->label('Tautan Pertemuan')
                            ->url(fn (Pelatihan $record): ?string => $record->pertemuan_url)
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->helperText('Hanya terlihat pengelola dan warga yang sudah masuk, tidak pernah di halaman publik.')
                            ->visible(fn (Pelatihan $record): bool => $record->punyaPertemuan()),

                        // Dipangkas tampilannya dan menangani `<p></p>` kosong bawaan
                        // RichEditor; lihat komponennya.
                        ViewEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->view('filament.infolists.components.deskripsi-ringkas')
                            ->columnSpanFull(),
                    ])->columns(2)->columnSpan(2),

                    Group::make()->schema([
                        ViewEntry::make('cover_view')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pelatihan-cover'),
                    ])->columnSpan(1),
                ]),
        ]);
    }
}
