<?php

namespace App\Filament\Resources\UmkmProducts\Schemas;

use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProduct;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

/**
 * Detail produk UMKM (baca-saja) untuk halaman View.
 * Menyajikan visual foto, harga, deskripsi, identitas usaha, serta tautan e-commerce.
 */
class UmkmProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama Produk')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'lg' => 3,
                ])
                ->schema([
                    ImageEntry::make('cover')
                        ->label('Foto Produk')
                        ->state(fn (UmkmProduct $record): string => $record->coverUrl())
                        ->height(240)
                        ->extraImgAttributes(['class' => 'rounded-xl object-cover shadow-sm'])
                        ->columnSpanFull(),

                    TextEntry::make('nama_produk')
                        ->label('Nama Produk')
                        ->weight(FontWeight::Bold)
                        ->columnSpanFull(),

                    TextEntry::make('harga')
                        ->label('Harga Produk')
                        ->money('IDR')
                        ->weight(FontWeight::Bold)
                        ->color('success')
                        ->placeholder('—'),

                    TextEntry::make('category.nama')
                        ->label('Kategori')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('jumlah_dilihat')
                        ->label('Jumlah Dilihat')
                        ->numeric()
                        ->icon('heroicon-m-eye')
                        ->iconColor('info')
                        ->suffix(' kali'),

                    TextEntry::make('created_at')
                        ->label('Ditambahkan')
                        ->dateTime('d M Y, H:i')
                        ->icon('heroicon-m-calendar')
                        ->iconColor('gray'),
                ]),

            Section::make('Deskripsi Produk')
                ->icon(Heroicon::OutlinedDocumentText)
                ->schema([
                    TextEntry::make('deskripsi')
                        ->hiddenLabel()
                        ->placeholder('Tidak ada deskripsi tambahan.')
                        ->columnSpanFull(),
                ]),

            // Blok ini untuk pengawas. Bagi pemilik, seluruh isinya adalah
            // identitasnya sendiri yang sudah ia lihat di halaman Usaha Saya.
            Section::make('Identitas Usaha & Pemilik')
                ->visible(fn (): bool => ! UmkmProfileResource::isSelfService())
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextEntry::make('umkmProfile.nama_usaha')
                        ->label('Nama Usaha / Lapak')
                        ->icon('heroicon-m-building-storefront')
                        ->iconColor('primary')
                        ->weight(FontWeight::Bold)
                        ->placeholder('—'),

                    TextEntry::make('umkmProfile.owner.name')
                        ->label('Pemilik Usaha')
                        ->icon('heroicon-m-user')
                        ->iconColor('gray')
                        ->placeholder('—'),

                    TextEntry::make('umkmProfile.nagari.nama')
                        ->label('Nagari')
                        ->badge()
                        ->color('info')
                        ->placeholder('—'),

                    TextEntry::make('umkmProfile.whatsapp')
                        ->label('Nomor WhatsApp')
                        ->icon('heroicon-m-phone')
                        ->iconColor('success')
                        ->copyable()
                        ->placeholder('—'),
                ]),

            Section::make('Tautan Promosi')
                ->icon(Heroicon::OutlinedLink)
                ->schema([
                    RepeatableEntry::make('tautan')
                        ->hiddenLabel()
                        ->placeholder('Belum ada tautan promosi untuk produk ini.')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('platform')
                                ->label('Platform')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('url')
                                ->label('URL Tautan')
                                ->url(fn (?string $state): ?string => $state)
                                ->openUrlInNewTab()
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->color('info'),
                        ]),
                ]),
        ]);
    }
}
