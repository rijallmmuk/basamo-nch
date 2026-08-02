<?php

namespace App\Filament\Resources\Evaluasis\Schemas;

use App\Enums\JenisEvaluasi;
use App\Models\Evaluasi;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class EvaluasiInfolist
{
    public static function configure(Schema $schema, ?JenisEvaluasi $jenis = null): Schema
    {
        return $schema->components([
            Section::make($jenis?->getLabel() ?? 'Informasi Evaluasi')
                ->icon('heroicon-o-information-circle')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('module_judul')
                        ->label('Modul Induk')
                        ->state(fn (Evaluasi $record): string => $record->module?->judul ?? '—')
                        ->weight(FontWeight::Bold)
                        ->size(TextSize::Large)
                        ->icon('heroicon-m-book-open')
                        ->columnSpanFull(),

                    TextEntry::make('nilai_lulus')
                        ->label('Nilai Kelulusan')
                        ->suffix(' / 100')
                        ->icon('heroicon-m-academic-cap')
                        ->weight(FontWeight::Bold)
                        ->visible(fn (Evaluasi $record): bool => ! $record->isPretest()),

                    TextEntry::make('maks_percobaan')
                        ->label('Batas Percobaan')
                        ->state(fn (Evaluasi $record): string => (int) $record->maks_percobaan === 0 ? 'Tidak dibatasi' : $record->maks_percobaan.' kali')
                        ->icon('heroicon-m-arrow-path')
                        ->visible(fn (Evaluasi $record): bool => ! $record->isPretest()),

                    TextEntry::make('pertanyaans_count')
                        ->label('Jumlah Soal')
                        ->state(fn (Evaluasi $record): string => ($record->pertanyaans_count ?? $record->pertanyaans()->count()).' soal')
                        ->badge()
                        ->color('info')
                        ->icon('heroicon-m-question-mark-circle'),

                    // Evaluasi tidak punya saklar terbit: ia terbuka sendiri begitu
                    // soalnya lengkap. Aturan itu harus terbaca, bukan ditebak.
                    TextEntry::make('kesiapan')
                        ->label('Status Pengerjaan')
                        ->state(fn (Evaluasi $record): string => $record->isReady() ? 'Siap dikerjakan' : 'Belum siap')
                        ->badge()
                        ->color(fn (Evaluasi $record): string => $record->isReady() ? 'success' : 'danger')
                        ->helperText(fn (Evaluasi $record): ?string => $record->isReady()
                            ? null
                            : 'Setiap soal butuh minimal dua pilihan, dengan sedikitnya satu jawaban benar dan satu jawaban salah.'),
                ]),
        ]);
    }
}
