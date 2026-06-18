<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
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
                            ->label('Username')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(User::class, 'username', ignoreRecord: true),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true)
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
                            ->placeholder('— Pilih nagari —')
                            ->visible(fn (Get $get) => auth()->user()?->isSuperAdmin() && $get('role') !== 'super_admin')
                            ->required(fn (Get $get) => auth()->user()?->isSuperAdmin() && $get('role') !== 'super_admin')
                            ->columnSpanFull(),
                    ]),

                Section::make('Keamanan')
                    ->columns(2)
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
