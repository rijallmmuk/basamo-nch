<?php

namespace App\Filament\Resources\Penduduks\Schemas;

use App\Enums\JenisKelamin;
use App\Models\Penduduk;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class PendudukInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            // KOLOM KIRI (2/3)
            Group::make()->schema([
                Section::make('Informasi Utama')
                    ->icon(Heroicon::OutlinedUser)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->columnSpanFull(),

                        TextEntry::make('nik')
                            ->label('NIK')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->inlineLabel(),

                        TextEntry::make('tempat_lahir')
                            ->label('Tempat & Tgl Lahir')
                            ->formatStateUsing(fn ($state, Penduduk $record): string => ($state ?: '—').', '
                                .($record->tanggal_lahir?->translatedFormat('d F Y') ?: '—'))
                            ->inlineLabel(),

                        TextEntry::make('tanggal_lahir')
                            ->label('Usia')
                            ->formatStateUsing(fn (Penduduk $record): string => $record->tanggal_lahir ? $record->tanggal_lahir->age.' tahun' : '—')
                            ->inlineLabel(),

                        TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn ($state): string => $state instanceof JenisKelamin ? $state->getLabel() : ($state ?: '—'))
                            ->inlineLabel(),

                        TextEntry::make('agama.nama')
                            ->label('Agama')
                            ->placeholder('—')
                            ->inlineLabel(),
                    ]),

                Section::make('Data Kependudukan')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('pendidikan.nama')
                            ->label('Pendidikan Terakhir')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('pekerjaan.nama')
                            ->label('Pekerjaan')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('statusPerkawinan.nama')
                            ->label('Status Perkawinan')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('nagari.nama')
                            ->label('Domisili (Nagari)')
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            // KOLOM KANAN (1/3)
            Group::make()->schema([
                Section::make('Kontak & Akun')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->schema([
                        TextEntry::make('user.status')
                            ->label('Status Akun')
                            ->badge()
                            ->placeholder('Belum ada akun')
                            ->inlineLabel(),

                        TextEntry::make('user.phone')
                            ->label('No. HP')
                            ->icon('heroicon-m-phone')
                            ->placeholder('—')
                            ->copyable()
                            ->inlineLabel(),

                        TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->placeholder('—')
                            ->copyable()
                            ->inlineLabel(),
                    ]),

                Section::make('Statistik Portal')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->visible(fn (Penduduk $record): bool => $record->user !== null)
                    ->schema([
                        TextEntry::make('modul_count')
                            ->label('Modul Dipelajari')
                            ->state(fn (Penduduk $record): int => $record->user?->moduleProgress()->count() ?? 0)
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('info')
                            ->icon('heroicon-m-book-open')
                            ->inlineLabel(),

                        TextEntry::make('kuis_count')
                            ->label('Kuis Dikerjakan')
                            ->state(fn (Penduduk $record): int => $record->user?->evaluasiPercobaans()->count() ?? 0)
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('warning')
                            ->icon('heroicon-m-academic-cap')
                            ->inlineLabel(),

                        TextEntry::make('diskusi_count')
                            ->label('Diskusi')
                            ->state(fn (Penduduk $record): int => $record->user?->discussions()->count() ?? 0)
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->inlineLabel(),
                    ]),

                Section::make('Lapak UMKM')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->visible(fn (Penduduk $record): bool => $record->user?->umkmProfile !== null)
                    ->schema([
                        TextEntry::make('user.umkmProfile.nama_usaha')
                            ->label('Nama Usaha')
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),

                        TextEntry::make('user.umkmProfile.status')
                            ->label('Status Lapak')
                            ->badge()
                            ->inlineLabel(),

                        TextEntry::make('user.umkmProfile.whatsapp')
                            ->label('WhatsApp')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('user.umkmProfile.alamat')
                            ->label('Alamat')
                            ->placeholder('—')
                            ->inlineLabel(),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ]);
    }
}
