<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\JenisKelamin;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    private const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'desa_admin' => 'Admin Desa',
        'warga' => 'Warga',
    ];

    private const ROLE_COLORS = [
        'super_admin' => 'danger',
        'desa_admin' => 'warning',
        'warga' => 'info',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Identitas dari tabel `penduduk` (hanya akun yang punya penduduk, mis. warga).
                Section::make('Identitas Kependudukan')
                    ->visible(fn (User $record): bool => $record->penduduk !== null)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('penduduk.nik')
                            ->label('NIK')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('penduduk.nama')
                            ->label('Nama lengkap')
                            ->placeholder('—'),
                        TextEntry::make('penduduk.tempat_lahir')
                            ->label('Tempat lahir')
                            ->placeholder('—'),
                        TextEntry::make('penduduk.tanggal_lahir')
                            ->label('Tanggal lahir')
                            ->date('d F Y')
                            ->placeholder('—'),
                        TextEntry::make('penduduk.jenis_kelamin')
                            ->label('Jenis kelamin')
                            ->formatStateUsing(fn ($state): string => $state instanceof JenisKelamin ? $state->getLabel() : ($state ?: '—')),
                        TextEntry::make('penduduk.agama.nama')
                            ->label('Agama')
                            ->placeholder('—'),
                        TextEntry::make('penduduk.statusPerkawinan.nama')
                            ->label('Status perkawinan')
                            ->placeholder('—'),
                        TextEntry::make('penduduk.pekerjaan.nama')
                            ->label('Pekerjaan')
                            ->placeholder('—'),
                    ]),

                Section::make('Akun')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('username')
                            ->label('Username')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => ! $record->isPortalAccount()),
                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->label('No. WhatsApp')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => $record->isPortalAccount()),
                        TextEntry::make('role')
                            ->label('Peran')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::ROLE_LABELS[$state] ?? ($state ?? '—'))
                            ->color(fn (?string $state): string => self::ROLE_COLORS[$state] ?? 'gray')
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('initial_otp')
                            ->label('OTP awal')
                            ->badge()
                            ->color('warning')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => $record->isPortalAccount()),
                        TextEntry::make('desa.nama')
                            ->label('Desa')
                            ->placeholder('Global')
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                        TextEntry::make('desaUnit.nama')
                            ->label('Wilayah')
                            ->placeholder('—'),
                    ]),

                Section::make('Aktivitas')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_xp')
                            ->label('Total XP')
                            ->numeric(),
                        TextEntry::make('umkm_access_granted_at')
                            ->label('Akses UMKM sejak')
                            ->dateTime('d M Y')
                            ->placeholder('Tidak ada'),
                        TextEntry::make('created_at')
                            ->label('Terdaftar')
                            ->dateTime('d M Y'),
                    ]),
            ]);
    }
}
