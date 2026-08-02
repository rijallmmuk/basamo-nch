<?php

namespace App\Filament\Resources\UmkmProfiles\Schemas;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

/**
 * Detail lapak. Pemilik melihat halaman ini sebagai "Usaha Saya", jadi baris yang
 * hanya berguna bagi pengawas (pemilik dan nagari) disembunyikan darinya, dan
 * status tayang diberi keterangan karena bukan ia yang mengubahnya.
 */
class UmkmProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Usaha')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('nama_usaha')
                        ->label('Nama Usaha')
                        ->weight(FontWeight::Bold)
                        ->size(TextSize::Large)
                        ->columnSpanFull(),

                    TextEntry::make('owner.name')
                        ->label('Pemilik Lapak')
                        ->icon('heroicon-m-user')
                        ->placeholder('—')
                        ->visible(fn (): bool => ! UmkmProfileResource::isSelfService()),

                    TextEntry::make('nagari.nama')
                        ->label('Nagari')
                        ->icon('heroicon-m-map-pin')
                        ->placeholder('—')
                        ->visible(fn (): bool => ! UmkmProfileResource::isSelfService()),

                    TextEntry::make('status')
                        ->label('Status Tayang')
                        ->badge()
                        // Pemilik tidak mengubah status sendiri, jadi keadaannya
                        // dijelaskan di sini alih-alih hanya berupa lencana.
                        ->helperText(fn (UmkmProfile $record): ?string => match (true) {
                            ! UmkmProfileResource::isSelfService() => null,
                            $record->status === ActiveStatus::Active => 'Usaha Anda tampil di katalog publik.',
                            default => 'Usaha Anda belum tampil di katalog publik. Hubungi Operator Nagari untuk menayangkannya.',
                        }),

                    TextEntry::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-m-phone')
                        ->placeholder('—'),

                    TextEntry::make('email')
                        ->label('Email')
                        ->icon('heroicon-m-envelope')
                        ->placeholder('—'),

                    TextEntry::make('jam_operasional')
                        ->label('Jam Operasional')
                        ->icon('heroicon-m-clock')
                        ->placeholder('—'),

                    TextEntry::make('tahun_berdiri')
                        ->label('Tahun Berdiri')
                        ->icon('heroicon-m-calendar')
                        ->placeholder('—'),

                    TextEntry::make('alamat')
                        ->label('Alamat Lengkap')
                        ->icon('heroicon-m-home')
                        ->placeholder('—')
                        ->columnSpan(2),

                    TextEntry::make('deskripsi')
                        ->label('Deskripsi Usaha')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Foto Etalase')
                ->icon(Heroicon::OutlinedPhoto)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    ImageEntry::make('logo')
                        ->label('Logo usaha')
                        ->state(fn (UmkmProfile $record): ?string => $record->logoUrl())
                        ->placeholder('Belum ada logo'),

                    ImageEntry::make('sampul')
                        ->label('Sampul etalase')
                        ->state(fn (UmkmProfile $record): ?string => $record->sampulUrl())
                        ->placeholder('Belum ada sampul'),

                    ImageEntry::make('qr')
                        ->label('Gambar QR')
                        ->state(fn (UmkmProfile $record): ?string => $record->qrUrl())
                        ->placeholder('Belum ada QR'),
                ]),

            Section::make('Tautan Promosi')
                ->icon(Heroicon::OutlinedLink)
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('tautan')
                        ->hiddenLabel()
                        ->placeholder('Belum ada tautan promosi.')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('platform')
                                ->label('Platform')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('url')
                                ->label('Tautan / URL')
                                ->url(fn (?string $state): ?string => $state)
                                ->openUrlInNewTab()
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->color('primary'),
                        ]),
                ]),
        ]);
    }
}
