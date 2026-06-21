<?php

namespace App\Filament\Resources\Desas\Schemas;

use App\Enums\ActiveStatus;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use App\Models\RefWilayah;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DesaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wilayah administratif')
                    ->description('Dipilih dari data resmi Kepmendagri (Sumatera Barat).')
                    ->columns(2)
                    ->schema([
                        // prov/kab/kec hanya bantu navigasi (tidak disimpan); wilayah_kode yang disimpan.
                        Select::make('prov_kode')
                            ->label('Provinsi')
                            ->options(fn () => RefWilayah::level(RefWilayah::LEVEL_PROVINSI)->orderBy('nama')->pluck('nama', 'kode'))
                            ->default('13')
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Set $set, Get $get) => $set('prov_kode', self::ancestor($get('wilayah_kode'), 1) ?? '13'))
                            ->afterStateUpdated(fn (Set $set) => self::resetBelow($set, 'prov')),

                        Select::make('kab_kode')
                            ->label('Kabupaten/Kota')
                            ->options(fn (Get $get) => RefWilayah::childrenOf($get('prov_kode'))->orderBy('nama')->pluck('nama', 'kode'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Set $set, Get $get) => $set('kab_kode', self::ancestor($get('wilayah_kode'), 2)))
                            ->afterStateUpdated(fn (Set $set) => self::resetBelow($set, 'kab')),

                        Select::make('kec_kode')
                            ->label('Kecamatan')
                            ->options(fn (Get $get) => RefWilayah::childrenOf($get('kab_kode'))->orderBy('nama')->pluck('nama', 'kode'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Set $set, Get $get) => $set('kec_kode', self::ancestor($get('wilayah_kode'), 3)))
                            ->afterStateUpdated(fn (Set $set) => self::resetBelow($set, 'kec')),

                        Select::make('wilayah_kode')
                            ->label('Desa/Kelurahan')
                            ->options(fn (Get $get) => RefWilayah::childrenOf($get('kec_kode'))->orderBy('nama')->pluck('nama', 'kode'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->live()
                            // Pilih desa/kel → isi otomatis nama + nama wilayah (disimpan denormalized).
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('nama', $state ? RefWilayah::find($state)?->nama : null);
                                $set('provinsi', RefWilayah::find(self::ancestor($state, 1))?->nama);
                                $set('kabupaten', RefWilayah::find(self::ancestor($state, 2))?->nama);
                                $set('kecamatan', RefWilayah::find(self::ancestor($state, 3))?->nama);
                            }),
                    ]),

                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Terisi otomatis dari pilihan wilayah; boleh disesuaikan.'),

                        TextInput::make('kode')
                            ->label('Kode')
                            ->required()
                            ->maxLength(50)
                            ->unique(Desa::class, 'kode', ignoreRecord: true)
                            ->placeholder('NCH-001')
                            ->helperText('Kode unik internal, mis. NCH-001.')
                            ->dehydrateStateUsing(fn (?string $state): string => Str::upper(trim((string) $state))),

                        Select::make('jenis_desa_id')
                            ->label('Penyebutan wilayah')
                            ->options(JenisDesa::orderBy('urutan')->pluck('nama', 'id'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->helperText('Sebutan administratif setingkat desa — mis. Desa / Kelurahan / Nagari.'),

                        Select::make('jenis_sub_unit_id')
                            ->label('Sebutan sub-unit (opsional)')
                            ->options(JenisSubUnit::orderBy('urutan')->pluck('nama', 'id'))
                            ->searchable()
                            ->native(false)
                            ->helperText('Boleh dikosongkan — admin desa dapat mengaturnya sendiri.'),

                        // Nama wilayah disimpan denormalized untuk display cepat (diisi dari pilihan di atas).
                        Hidden::make('provinsi'),
                        Hidden::make('kabupaten'),
                        Hidden::make('kecamatan'),
                    ]),

                Section::make('Koordinat')
                    ->columns(2)
                    ->schema([
                        TextInput::make('koordinat_lat')
                            ->label('Lintang (lat)')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),

                        TextInput::make('koordinat_lng')
                            ->label('Bujur (lng)')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                    ]),

                Section::make('Logo')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo desa')
                            ->collection('logo')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Opsional. Logo kabupaten/kota otomatis dari data wilayah.'),
                    ]),

                Section::make('Kontak & Status')
                    ->columns(2)
                    ->schema([
                        TextInput::make('kontak')
                            ->label('Kontak')
                            ->tel()
                            ->maxLength(20),

                        Select::make('status')
                            ->label('Status')
                            ->options(ActiveStatus::class)
                            ->default('active')
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    /** Kode leluhur pada `n` segmen pertama (1=prov, 2=kab, 3=kec). */
    protected static function ancestor(?string $kode, int $segments): ?string
    {
        if (! $kode) {
            return null;
        }

        $parts = explode('.', $kode);

        return count($parts) >= $segments ? implode('.', array_slice($parts, 0, $segments)) : null;
    }

    /** Kosongkan pilihan di bawah level yang berubah agar tak inkonsisten. */
    protected static function resetBelow(Set $set, string $level): void
    {
        if ($level === 'prov') {
            $set('kab_kode', null);
        }

        if (in_array($level, ['prov', 'kab'], true)) {
            $set('kec_kode', null);
        }

        $set('wilayah_kode', null);
    }
}
