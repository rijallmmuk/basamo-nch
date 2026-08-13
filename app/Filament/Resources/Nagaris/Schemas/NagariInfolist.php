<?php

namespace App\Filament\Resources\Nagaris\Schemas;

use App\Enums\StatusIdm;
use App\Models\Module;
use App\Models\Nagari;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

/** Ringkasan nagari (baca-saja) untuk halaman detail — identitas, wilayah, admin, peta. */
class NagariInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            // KOLOM KIRI (2/3)
            Group::make()->schema([
                Section::make('Informasi Utama')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->columnSpanFull(),

                        // Alamat publik nagari, ditampilkan utuh berikut domainnya
                        // supaya bisa disalin dan langsung dipakai.
                        TextEntry::make('slug')
                            ->label('Alamat Situs')
                            ->state(fn (Nagari $record): string => $record->slug.'.'.config('app.public_base_domain'))
                            ->url(fn (Nagari $record): string => 'https://'.$record->slug.'.'.config('app.public_base_domain'))
                            ->openUrlInNewTab()
                            ->icon('heroicon-m-globe-alt')
                            ->copyable()
                            ->copyableState(fn (Nagari $record): string => $record->slug.'.'.config('app.public_base_domain'))
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpanFull()
                            ->inlineLabel(),

                        TextEntry::make('wilayah_kode')
                            ->label('Kode Wilayah')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('provinsi')
                            ->label('Provinsi')
                            ->icon('heroicon-m-map')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->inlineLabel(),

                        TextEntry::make('kabupaten')
                            ->label('Kabupaten/Kota')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('created_at')
                            ->label('Tanggal Daftar')
                            ->dateTime('d M Y')
                            ->color('gray')
                            ->inlineLabel(),

                        TextEntry::make('kecamatan')
                            ->label('Kecamatan')
                            ->icon('heroicon-m-building-office-2')
                            ->placeholder('—')
                            ->inlineLabel(),
                    ]),

                Section::make('Peta & Lokasi Geografis')
                    ->icon(Heroicon::OutlinedMap)
                    ->schema([
                        ViewEntry::make('map')
                            ->label('')
                            ->view('filament.nagari.peta-batas')
                            ->columnSpanFull(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            // KOLOM KANAN (1/3)
            Group::make()->schema([
                Section::make('Galeri Sampul')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('sampul')
                            ->label('')
                            ->view('filament.infolists.components.nagari-sampul-slider'),
                    ]),

                Section::make('Statistik Capaian')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        TextEntry::make('warga_count')
                            ->label('Penduduk')
                            ->state(fn (Nagari $record): int => $record->warga()->count())
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('info')
                            ->icon('heroicon-m-users')
                            ->inlineLabel(),

                        TextEntry::make('umkm_count')
                            ->label('UMKM')
                            ->state(fn (Nagari $record): int => $record->umkmProfiles()->count())
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->icon('heroicon-m-building-storefront')
                            ->inlineLabel(),

                        TextEntry::make('modul_count')
                            ->label('Modul LMS')
                            ->state(fn (Nagari $record): int => Module::whereIn('pelatihan_id', $record->pelatihans()->select('pelatihans.id'))->count())
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('warning')
                            ->icon('heroicon-m-book-open')
                            ->inlineLabel(),

                        TextEntry::make('sdgs_skor')
                            ->label('Skor SDGs')
                            ->state(fn (Nagari $record): string => number_format($record->sdgAchievements()->avg('persentase') ?? 0, 2).'%')
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->icon('heroicon-m-chart-pie')
                            ->inlineLabel(),
                    ]),

                Section::make('Indeks Desa Membangun (IDM)')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('latestIdmStatus.status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => StatusIdm::tryFrom((string) $state)?->label() ?? (string) $state)
                            ->color(fn (?string $state): string => StatusIdm::tryFrom((string) $state)?->color() ?? 'gray')
                            ->placeholder('Belum ada data')
                            ->inlineLabel(),

                        TextEntry::make('latestIdmStatus.skor')
                            ->label('Skor IDM')
                            ->weight(FontWeight::Bold)
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('latestIdmStatus.tahun')
                            ->label('Tahun Data')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('latestIdmStatus.target_status')
                            ->label('Target Berikutnya')
                            ->formatStateUsing(fn (?string $state): ?string => StatusIdm::tryFrom((string) $state)?->label() ?? $state)
                            ->color('gray')
                            ->placeholder('—')
                            ->inlineLabel(),
                    ]),

                Section::make('Operator')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('operator.email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-m-envelope')
                            ->placeholder('—')
                            ->inlineLabel(),

                        TextEntry::make('operator.phone')
                            ->label('No. HP')
                            ->copyable()
                            ->icon('heroicon-m-phone')
                            ->placeholder('—')
                            ->inlineLabel(),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ]);
    }
}
