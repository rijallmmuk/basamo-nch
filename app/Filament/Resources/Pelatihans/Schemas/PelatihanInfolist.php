<?php

namespace App\Filament\Resources\Pelatihans\Schemas;

use App\Models\Pelatihan;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
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

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pelatihan')
                ->icon(Heroicon::OutlinedAcademicCap)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Group::make()->schema([
                        TextEntry::make('tema.nama')
                            ->label('Tema Pelatihan')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->label('Akses Warga')
                            ->badge(),

                        TextEntry::make('modules_count')
                            ->label('Jumlah Modul')
                            ->state(fn (Pelatihan $record): int => $record->modules()->count())
                            ->icon('heroicon-m-book-open')
                            ->color('info')
                            ->weight(FontWeight::Bold),

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

                        TextEntry::make('cakupan')
                            ->label('Sasaran Pelatihan')
                            ->state(function (Pelatihan $record): string {
                                if ($record->semua_nagari) {
                                    return 'Semua nagari';
                                }

                                $nagaris = $record->nagaris;

                                return match (true) {
                                    $nagaris->isEmpty() => 'Belum ada',
                                    $nagaris->count() === 1 => (string) $nagaris->first()->nama,
                                    default => $nagaris->pluck('nama')->join(', '),
                                };
                            })
                            ->badge()
                            ->color('info'),

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

                        // Dipangkas tampilannya dan menangani `<p></p>` kosong bawaan
                        // RichEditor; lihat komponennya.
                        ViewEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->view('filament.infolists.components.deskripsi-ringkas')
                            ->columnSpanFull(),
                    ])->columns(2)->columnSpan(fn (Pelatihan $record): int => $record->punyaCover() ? 2 : 3),

                    // Sampul hanya bila benar-benar diunggah. Yang digambar sistem
                    // diturunkan dari nama tema yang sudah tertera di halaman ini.
                    Group::make()->schema([
                        ViewEntry::make('cover_view')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pelatihan-cover'),
                    ])
                        ->columnSpan(1)
                        ->visible(fn (Pelatihan $record): bool => $record->punyaCover()),
                ]),
        ]);
    }
}
