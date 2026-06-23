<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Pekerjaan;
use App\Models\StatusPerkawinan;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama lengkap')
                            ->required()
                            ->maxLength(255),

                        // Warga: NIK (login portal). Admin: username.
                        TextInput::make('nik')
                            ->label('NIK')
                            ->visible(fn (Get $get): bool => static::isPortalRole($get))
                            ->required(fn (Get $get): bool => static::isPortalRole($get))
                            ->rules(['digits:16'])
                            ->helperText('NIK 16 digit — dipakai warga untuk login portal.')
                            ->unique(User::class, 'nik', ignoreRecord: true),

                        TextInput::make('username')
                            ->label('Username')
                            ->visible(fn (Get $get): bool => ! static::isPortalRole($get))
                            ->required(fn (Get $get): bool => ! static::isPortalRole($get))
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->helperText('Dipakai admin untuk login panel.')
                            ->unique(User::class, 'username', ignoreRecord: true),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(fn (Get $get): bool => ! static::isPortalRole($get))
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('Opsional. Untuk komunikasi & menyampaikan kode OTP.')
                            ->visible(fn (Get $get): bool => static::isPortalRole($get))
                            ->columnSpanFull(),

                        Select::make('desa_unit_id')
                            ->label(fn (Get $get): string => static::wilayahLabel($get))
                            ->options(fn (Get $get): array => static::wilayahOptions($get))
                            ->searchable()
                            ->placeholder('— Pilih —')
                            ->helperText('Opsional. Atur daftarnya di menu Wilayah.')
                            ->visible(fn (Get $get): bool => static::isPortalRole($get))
                            // Pertahanan server-side: wilayah harus milik desa warga.
                            ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value) {
                                    return;
                                }

                                $desaId = static::resolveDesaId($get);

                                if (! DesaUnit::whereKey($value)->where('desa_id', $desaId)->exists()) {
                                    $fail('Wilayah tidak sesuai dengan desa.');
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // Data kependudukan warga → disimpan ke tabel `penduduk` (bukan `users`).
                // Lihat InteractsWithPenduduk + PendudukService.
                Section::make('Data Kependudukan')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => static::isPortalRole($get))
                    ->schema([
                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(100),

                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->maxDate(now()),

                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->native(false),

                        Select::make('agama_id')
                            ->label('Agama')
                            ->options(fn (): array => Agama::where('aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->preload(),

                        Select::make('status_perkawinan_id')
                            ->label('Status Perkawinan')
                            ->options(fn (): array => StatusPerkawinan::where('aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all())
                            ->native(false),

                        Select::make('pekerjaan_id')
                            ->label('Pekerjaan')
                            ->options(fn (): array => Pekerjaan::where('aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Akses')
                    ->columns(2)
                    ->schema([
                        // Admin desa hanya mengelola warga → pilihan peran disembunyikan
                        // (peran dipaksa 'warga' di server). Hanya super_admin yang memilih peran.
                        Select::make('role')
                            ->label('Peran')
                            ->options(fn () => static::roleOptions())
                            ->default('warga')
                            ->required()
                            ->native(false)
                            ->live()
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                        Select::make('status')
                            ->label('Status')
                            ->options(ActiveStatus::class)
                            ->default('active')
                            ->required()
                            ->native(false),

                        // super_admin tidak terikat desa (global). Field hanya untuk super_admin
                        // yang mengelola peran selain super_admin; untuk desa_admin, desa
                        // dipaksa ke miliknya sendiri di halaman Create (tidak tampil di form).
                        Select::make('desa_id')
                            ->label('Desa')
                            ->relationship('desa', 'nama')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, callable $set) => $set('desa_unit_id', null))
                            ->placeholder('— Pilih desa —')
                            ->visible(fn (Get $get) => auth()->user()?->isSuperAdmin() && $get('role') !== 'super_admin')
                            ->required(fn (Get $get) => auth()->user()?->isSuperAdmin() && $get('role') !== 'super_admin')
                            ->columnSpanFull(),
                    ]),

                TextInput::make('initial_otp')
                    ->label('Kode OTP awal')
                    ->maxLength(12)
                    ->visible(fn (Get $get): bool => static::isPortalRole($get))
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Sandi awal warga — kosongkan untuk OTP otomatis. Ditampilkan setelah akun dibuat; wajib diganti saat login pertama, lalu terhapus. Saat edit, pakai aksi "Reset OTP".'),

                Section::make('Keamanan')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => ! static::isPortalRole($get))
                    ->schema([
                        TextInput::make('password')
                            ->label('Kata sandi')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->autocomplete('new-password')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed()
                            ->helperText('Minimal 8 karakter. Kosongkan saat edit bila tidak ingin mengganti.'),

                        TextInput::make('password_confirmation')
                            ->label('Ulangi kata sandi')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation, Get $get): bool => $operation === 'create' || filled($get('password'))),
                    ]),
            ]);
    }

    /** Peran portal (warga) → login NIK + OTP, tanpa sandi manual. */
    protected static function isPortalRole(Get $get): bool
    {
        // Admin desa hanya mengelola warga → selalu mode warga (field peran disembunyikan).
        if (auth()->user()?->isDesaAdmin()) {
            return true;
        }

        return $get('role') === 'warga';
    }

    /** Desa konteks: desa_admin → miliknya; super_admin → pilihan di form. */
    protected static function resolveDesaId(Get $get): ?int
    {
        $actor = auth()->user();

        return $actor?->isDesaAdmin()
            ? $actor->desa_id
            : ($get('desa_id') ? (int) $get('desa_id') : null);
    }

    /** Daftar wilayah untuk desa konteks (untuk Select alamat warga). */
    protected static function wilayahOptions(Get $get): array
    {
        $desaId = static::resolveDesaId($get);

        if (! $desaId) {
            return [];
        }

        return DesaUnit::where('desa_id', $desaId)
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /** Label field mengikuti sebutan wilayah desa (Jorong/Dusun/…). */
    protected static function wilayahLabel(Get $get): string
    {
        $desaId = static::resolveDesaId($get);

        return $desaId
            ? (Desa::find($desaId)?->jenisSubUnit?->nama ?: 'Sub-Unit Wilayah')
            : 'Sub-Unit Wilayah';
    }

    /**
     * super_admin boleh menetapkan semua peran; desa_admin hanya boleh
     * membuat akun warga (tidak boleh membuat admin). Akses UMKM diberikan
     * terpisah lewat aksi tabel, bukan saat memilih peran.
     *
     * @return array<string, string>
     */
    protected static function roleOptions(): array
    {
        if (auth()->user()?->isDesaAdmin()) {
            return [
                'warga' => 'Warga',
            ];
        }

        return [
            'super_admin' => 'Super Admin',
            'desa_admin' => 'Admin Desa',
            'warga' => 'Warga',
        ];
    }
}
