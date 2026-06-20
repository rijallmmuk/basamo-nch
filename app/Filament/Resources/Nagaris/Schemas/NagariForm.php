<?php

namespace App\Filament\Resources\Nagaris\Schemas;

use App\Models\Nagari;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class NagariForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama nagari')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('kode')
                            ->label('Kode')
                            ->required()
                            ->maxLength(50)
                            ->unique(Nagari::class, 'kode', ignoreRecord: true)
                            ->placeholder('NCH-001')
                            ->helperText('Kode unik nagari, mis. NCH-001.')
                            ->dehydrateStateUsing(fn (?string $state): string => Str::upper(trim((string) $state))),

                        TextInput::make('wilayah_label')
                            ->label('Sebutan unit wilayah')
                            ->default('Jorong')
                            ->required()
                            ->maxLength(30)
                            ->datalist(['Jorong', 'Korong', 'Kampuang', 'Dusun'])
                            ->helperText('Sebutan bagian dalam nagari ini — mis. Jorong / Korong / Dusun.'),
                    ]),

                Section::make('Wilayah')
                    ->columns(3)
                    ->schema([
                        TextInput::make('provinsi')->label('Provinsi')->maxLength(100),
                        TextInput::make('kabupaten')->label('Kabupaten/Kota')->maxLength(100),
                        TextInput::make('kecamatan')->label('Kecamatan')->maxLength(100),

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
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo nagari')
                            ->collection('logo')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Opsional. JPG/PNG/WEBP/SVG, maks 2 MB.'),

                        SpatieMediaLibraryFileUpload::make('logo_kabupaten')
                            ->label('Logo kabupaten/kota')
                            ->collection('logo_kabupaten')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Logo kabupaten/kota induk nagari ini.'),
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
                            ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }
}
