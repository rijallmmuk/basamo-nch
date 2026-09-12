<?php

namespace App\Filament\Resources\Beritas\Schemas;

use App\Models\Berita;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class BeritaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Publikasi')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('kategori')
                        ->label('Kategori')
                        ->badge(),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),

                    TextEntry::make('target_audience')
                        ->label('Sasaran Nagari')
                        ->state(fn (Berita $record): string => $record->targetAudienceLabel())
                        ->badge()
                        ->color('info'),

                    TextEntry::make('published_at')
                        ->label('Waktu Terbit')
                        ->dateTime('d F Y, H:i')
                        ->placeholder('Belum terbit'),

                    TextEntry::make('penulis')
                        ->label('Penulis / Sumber')
                        ->state(fn (Berita $record): string => $record->authorLabel()),

                    TextEntry::make('views_count')
                        ->label('Jumlah Pembaca')
                        ->numeric()
                        ->suffix(' kali dilihat'),

                    TextEntry::make('is_pinned')
                        ->label('Sorotan Utama')
                        ->state(fn (Berita $record): string => $record->is_pinned ? 'Disematkan (Pinned)' : 'Standar')
                        ->badge()
                        ->color(fn (Berita $record): string => $record->is_pinned ? 'warning' : 'gray'),

                    TextEntry::make('reading_time')
                        ->label('Estimasi Waktu Baca')
                        ->state(fn (Berita $record): string => $record->readingTime() . ' menit'),

                    TextEntry::make('slug')
                        ->label('Slug URL')
                        ->copyable(),
                ]),

            Section::make('Foto Sampul')
                ->icon(Heroicon::OutlinedPhoto)
                ->columnSpanFull()
                ->schema([
                    ImageEntry::make('sampul')
                        ->hiddenLabel()
                        ->state(fn (Berita $record): ?string => $record->sampulUrl('hero'))
                        ->placeholder('Belum ada foto sampul.')
                        ->columnSpanFull(),
                ]),

            Section::make('Isi Publikasi')
                ->icon(Heroicon::OutlinedDocumentText)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('judul')
                        ->hiddenLabel()
                        ->weight(FontWeight::Bold)
                        ->size(TextSize::Large)
                        ->columnSpanFull(),

                    TextEntry::make('ringkasan')
                        ->label('Ringkasan / Cuplikan')
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('konten')
                        ->label('Isi Lengkap')
                        ->html()
                        ->columnSpanFull(),
                ]),

            Section::make('Berkas Lampiran')
                ->icon(Heroicon::OutlinedPaperClip)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('lampiran_list')
                        ->hiddenLabel()
                        ->state(function (Berita $record): string {
                            $media = $record->getMedia('lampiran');
                            if ($media->isEmpty()) {
                                return '<span class="text-gray-400 dark:text-gray-500">Tidak ada berkas lampiran.</span>';
                            }

                            return $media->map(function ($file) {
                                $url = e($file->getUrl());
                                $name = e($file->file_name);
                                $size = e($file->human_readable_size);

                                return "<div class='flex items-center gap-2 py-1'><a href='{$url}' target='_blank' class='text-primary-600 dark:text-primary-400 font-medium hover:underline flex items-center gap-1.5'><span>📄</span> {$name}</a> <span class='text-xs text-gray-500'>({$size})</span></div>";
                            })->join('');
                        })
                        ->html()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
