<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Nagari;
use App\Models\User;
use App\Models\Wilayah;
use Closure;
use Filament\Forms\Components\Placeholder;
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

                        TextInput::make('username')
                            ->label(fn (Get $get): string => static::isPortalRole($get) ? 'NIK' : 'Username')
                            ->required()
                            ->maxLength(255)
                            ->rules(fn (Get $get): array => static::isPortalRole($get) ? ['digits:16'] : ['alpha_dash'])
                            ->helperText(fn (Get $get): ?string => static::isPortalRole($get)
                                ? 'NIK 16 digit — dipakai warga untuk login portal.'
                                : null)
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

                        Select::make('wilayah_id')
                            ->label(fn (Get $get): string => static::wilayahLabel($get))
                            ->options(fn (Get $get): array => static::wilayahOptions($get))
                            ->searchable()
                            ->placeholder('— Pilih —')
                            ->helperText('Opsional. Atur daftarnya di menu Wilayah.')
                            ->visible(fn (Get $get): bool => static::isPortalRole($get))
                            // Pertahanan server-side: wilayah harus milik nagari warga.
                            ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value) {
                                    return;
                                }

                                $nagariId = static::resolveNagariId($get);

                                if (! Wilayah::whereKey($value)->where('nagari_id', $nagariId)->exists()) {
                                    $fail('Wilayah tidak sesuai dengan nagari.');
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Akses')
                    ->columns(2)
                    ->schema([
                        Select::make('role')
                            ->label('Peran')
                            ->options(fn () => static::roleOptions())
                            ->required()
                            ->native(false)
                            ->live(),

                        Select::make('status')
                            ->label('Status')
                            ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])
                            ->default('active')
                            ->required()
                            ->native(false),

                        // super_admin tidak terikat nagari (global). Field hanya untuk super_admin
                        // yang mengelola peran selain super_admin; untuk nagari_admin, nagari
                        // dipaksa ke miliknya sendiri di halaman Create (tidak tampil di form).
                        Select::make('nagari_id')
                            ->label('Nagari')
                            ->relationship('nagari', 'nama')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, callable $set) => $set('wilayah_id', null))
                            ->placeholder('— Pilih nagari —')
                            ->visible(fn (Get $get) => auth()->user()?->isSuperAdmin() && $get('role') !== 'super_admin')
                            ->required(fn (Get $get) => auth()->user()?->isSuperAdmin() && $get('role') !== 'super_admin')
                            ->columnSpanFull(),
                    ]),

                Placeholder::make('otp_info')
                    ->label('Sandi awal (OTP)')
                    ->content('Sistem membuat kode OTP otomatis sebagai sandi awal. Kode ditampilkan setelah akun dibuat — sampaikan ke warga. Warga wajib menggantinya saat login pertama. Untuk menerbitkan ulang, pakai aksi "Reset OTP".')
                    ->visible(fn (Get $get): bool => static::isPortalRole($get)),

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

    /** Peran portal (warga/umkm_owner) → login NIK + OTP, tanpa sandi manual. */
    protected static function isPortalRole(Get $get): bool
    {
        return in_array($get('role'), ['warga', 'umkm_owner'], true);
    }

    /** Nagari konteks: nagari_admin → miliknya; super_admin → pilihan di form. */
    protected static function resolveNagariId(Get $get): ?int
    {
        $actor = auth()->user();

        return $actor?->isNagariAdmin()
            ? $actor->nagari_id
            : ($get('nagari_id') ? (int) $get('nagari_id') : null);
    }

    /** Daftar wilayah untuk nagari konteks (untuk Select alamat warga). */
    protected static function wilayahOptions(Get $get): array
    {
        $nagariId = static::resolveNagariId($get);

        if (! $nagariId) {
            return [];
        }

        return Wilayah::where('nagari_id', $nagariId)
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /** Label field mengikuti sebutan wilayah nagari (Jorong/Dusun/…). */
    protected static function wilayahLabel(Get $get): string
    {
        $nagariId = static::resolveNagariId($get);

        return $nagariId
            ? (Nagari::find($nagariId)?->wilayah_label ?? 'Wilayah')
            : 'Wilayah';
    }

    /**
     * super_admin boleh menetapkan semua peran; nagari_admin hanya boleh
     * membuat akun warga / pemilik UMKM (tidak boleh membuat admin).
     *
     * @return array<string, string>
     */
    protected static function roleOptions(): array
    {
        if (auth()->user()?->isNagariAdmin()) {
            return [
                'warga' => 'Warga',
                'umkm_owner' => 'Pemilik UMKM',
            ];
        }

        return [
            'super_admin' => 'Super Admin',
            'nagari_admin' => 'Admin Nagari',
            'warga' => 'Warga',
            'umkm_owner' => 'Pemilik UMKM',
        ];
    }
}
