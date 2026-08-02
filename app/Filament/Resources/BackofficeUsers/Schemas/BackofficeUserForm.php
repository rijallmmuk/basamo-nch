<?php

namespace App\Filament\Resources\BackofficeUsers\Schemas;

use App\Enums\ActiveStatus;
use App\Models\User;
use App\Services\BackofficeUserService;
use App\Support\PhoneNumber;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class BackofficeUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Pengguna & Login')
                ->icon(Heroicon::OutlinedUser)
                ->description('Identitas dan kredensial untuk masuk ke panel.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->prefixIcon('heroicon-m-user')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, ?string $state, callable $set, callable $get, ?User $record): void {
                            if (blank($state)) {
                                if ($operation === 'create') {
                                    $set('username', null);
                                }

                                return;
                            }

                            $currentUsername = (string) $get('username');

                            if ($operation === 'create' || blank($currentUsername)) {
                                $username = app(BackofficeUserService::class)->generateUniqueUsername((string) $state, $record?->id);
                                $set('username', $username);
                            }
                        })
                        ->disabled(fn (?User $record): bool => $record?->penduduk_id !== null),

                    TextInput::make('username')
                        ->label('Username')
                        ->prefixIcon('heroicon-m-identification')
                        ->nullable()
                        ->alphaDash()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->unique(User::class, 'username', ignoreRecord: true)
                        ->disabled(fn (?User $record): bool => ($record?->hasRole('operator') ?? false)
                            || $record?->penduduk_id !== null)
                        ->helperText('Kosongkan agar dibuat otomatis dari kata pertama nama dan tiga angka.'),

                    TextInput::make('email')
                        ->label('Alamat Email')
                        ->prefixIcon('heroicon-m-envelope')
                        ->email()
                        ->maxLength(255)
                        ->unique(User::class, 'email', ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::lower(trim($state)) : null),

                    TextInput::make('phone')
                        ->label('No. HP / WhatsApp')
                        ->prefixIcon('heroicon-m-phone')
                        ->tel()
                        ->maxLength(20)
                        ->regex(PhoneNumber::REGEX),

                    TextInput::make('lembaga')
                        ->label('Lembaga atau Instansi')
                        ->prefixIcon('heroicon-m-building-office-2')
                        ->maxLength(255)
                        ->placeholder('Isi lembaga atau instansi')
                        ->helperText('Khusus akun pengajar.'),

                    Select::make('status')
                        ->label('Status Akun')
                        ->prefixIcon('heroicon-m-check-circle')
                        ->options(ActiveStatus::class)
                        ->default('active')
                        ->required(),

                    // HANYA saat Tambah. Saat Ubah, mengetik sandi baru di sini berarti
                    // admin menetapkan sandi yang ia sendiri tahu untuk akun orang lain,
                    // dan tanpa flag wajib-ganti sandi itu bertahan selamanya. Jalur
                    // pemulihan yang benar adalah aksi "Reset password", yang
                    // mengembalikan sandi awal peran sekaligus mewajibkan penggantian.
                    TextInput::make('password')
                        ->label('Kata Sandi')
                        ->prefixIcon('heroicon-m-key')
                        ->password()
                        ->revealable()
                        ->nullable()
                        ->minLength(8)
                        ->hiddenOn('edit')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Kosongkan untuk memakai sandi awal bawaan. Pengguna wajib menggantinya saat pertama masuk.')
                        ->columnSpanFull(),
                ]),

            Section::make('Peran & Otorisasi Hak Akses')
                ->icon(Heroicon::OutlinedShieldCheck)
                // Isian Nagari sengaja TIDAK ada di sini. Ketiga peran yang dapat
                // dibuat lewat form ini (superadmin, pengajar, DPMD) semuanya bekerja
                // lintas nagari: tak satu pun scoping membaca `users.nagari_id` untuk
                // mereka, hanya untuk operator yang dibuat lewat penyiapan Nagari.
                // Menampilkannya hanya membuat admin mengira akses bisa dipersempit.
                ->description('Satu akun satu peran. Superadmin, pengajar, dan DPMD bekerja lintas nagari; operator dibuat lewat menu Nagari.')
                ->columns(1)
                ->schema([
                    Select::make('role_names')
                        ->label('Peran')
                        ->prefixIcon('heroicon-m-shield-check')
                        // Satu akun = satu peran. Sebelumnya field ini multiple
                        // padahal BackofficeUserService sudah menolak lebih dari
                        // satu, jadi pengguna baru tahu setelah menekan Simpan.
                        ->native(false)
                        ->options(function (string $operation, ?User $record): array {
                            $coreRoles = collect([
                                'superadmin' => 'Superadmin (Lintas Nagari)',
                                'pengajar' => 'Pengajar (Lintas Nagari)',
                                'dpmd' => 'DPMD (Lintas Nagari)',
                            ]);

                            if ($operation === 'edit' && ($record?->hasRole('operator') ?? false)) {
                                $coreRoles->put('operator', 'Operator Nagari');
                            }

                            return $coreRoles->all();
                        })
                        ->required(fn (?User $record): bool => $record?->penduduk_id === null)
                        ->helperText('Pilih tepat satu peran.'),
                ]),
        ]);
    }
}
