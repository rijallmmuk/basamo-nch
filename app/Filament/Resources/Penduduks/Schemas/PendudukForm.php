<?php

namespace App\Filament\Resources\Penduduks\Schemas;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Models\Agama;
use App\Models\Pekerjaan;
use App\Models\Pendidikan;
use App\Models\Penduduk;
use App\Models\StatusPerkawinan;
use App\Models\User;
use App\Support\NagariContext;
use App\Support\PhoneNumber;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PendudukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identitas Pribadi')
                    ->description('Setiap warga otomatis memperoleh satu akun login dengan role warga.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama lengkap')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedUser),
                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->rules(['digits:16'])
                            ->unique(Penduduk::class, 'nik', ignoreRecord: true)
                            ->prefixIcon(Heroicon::OutlinedIdentification)
                            ->validationMessages([
                                'unique' => 'NIK ini sudah terdaftar. Jika terarsip, pulihkan dari filter Dihapus.',
                            ]),
                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(100)
                            ->prefixIcon(Heroicon::OutlinedMapPin)
                            ->required(static::requiredOnCreate()),
                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->maxDate(now())
                            ->prefixIcon(Heroicon::OutlinedCalendar)
                            ->required(static::requiredOnCreate()),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->native(false)
                            ->prefixIcon(Heroicon::OutlinedUserGroup)
                            ->required(static::requiredOnCreate()),
                    ]),
                Section::make('Data Kependudukan')
                    ->icon(Heroicon::OutlinedUsers)
                    ->columns(2)
                    ->schema([
                        Select::make('agama_id')
                            ->label('Agama')
                            ->options(fn (Get $get): array => Agama::options((int) $get('agama_id') ?: null))
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-heart')
                            ->required(static::requiredOnCreate()),
                        Select::make('status_perkawinan_id')
                            ->label('Status Perkawinan')
                            ->options(fn (Get $get): array => StatusPerkawinan::options((int) $get('status_perkawinan_id') ?: null))
                            ->native(false)
                            ->prefixIcon('heroicon-o-link')
                            ->required(static::requiredOnCreate()),
                        Select::make('pendidikan_id')
                            ->label('Pendidikan Terakhir')
                            ->options(fn (Get $get): array => Pendidikan::options((int) $get('pendidikan_id') ?: null))
                            ->native(false)
                            ->prefixIcon(Heroicon::OutlinedAcademicCap)
                            ->required(static::requiredOnCreate()),
                        Select::make('pekerjaan_id')
                            ->label('Pekerjaan')
                            ->options(fn (Get $get): array => Pekerjaan::options((int) $get('pekerjaan_id') ?: null))
                            ->searchable()
                            ->preload()
                            ->prefixIcon(Heroicon::OutlinedBriefcase)
                            ->required(static::requiredOnCreate()),
                    ]),
                Section::make('Kontak & Akun')
                    ->description('Data berikut disimpan pada akun login yang terhubung satu-ke-satu.')
                    ->icon(Heroicon::OutlinedPhone)
                    ->columns(2)
                    ->schema([
                        Select::make('nagari_id')
                            ->label('Nagari')
                            ->relationship('nagari', 'nama')
                            ->searchable()
                            ->preload()
                            ->prefixIcon(Heroicon::OutlinedHomeModern)
                            ->visible(fn (): bool => auth()->user()?->managedNagariId(NagariContext::WARGA) === null)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->required(fn (string $operation): bool => $operation === 'create'
                                && auth()->user()?->managedNagariId(NagariContext::WARGA) === null)
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label('No. HP')
                            ->tel()
                            ->maxLength(20)
                            ->regex(PhoneNumber::REGEX)
                            ->prefixIcon(Heroicon::OutlinedPhone),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->rule(fn (?Penduduk $record) => Rule::unique((new User)->getTable(), 'email')->ignore($record?->user?->getKey()))
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::lower(trim($state)) : null),
                        Select::make('status')
                            ->label('Status Akun')
                            ->options(ActiveStatus::class)
                            ->default('active')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-shield-check')
                            ->helperText('Nonaktif = akun warga tidak dapat login (mis. pindah). Gunakan Hapus untuk mengarsipkan.'),
                    ]),
            ]);
    }

    protected static function requiredOnCreate(): Closure
    {
        return fn (string $operation): bool => $operation === 'create';
    }
}
