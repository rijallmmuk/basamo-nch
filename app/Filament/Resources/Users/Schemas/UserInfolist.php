<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\JenisKelamin;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Susunan sengaja mengikuti form Edit (UserForm) agar tampilan Lihat & Edit
                // konsisten: section bertumpuk atas-bawah (full width), judul di atas.
                Section::make('Identitas')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('penduduk.nama')
                            ->label('Nama lengkap')
                            ->placeholder('—'),
                        TextEntry::make('penduduk.nik')
                            ->label('NIK')
                            ->copyable()
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
                    ]),

                Section::make('Data Sosial')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
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

                Section::make('Alamat')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('desa.nama')
                            ->label('Desa')
                            ->placeholder('—')
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                        // Label ikut sebutan sub-unit desa (Jorong/Korong/Dusun) — seperti di tabel & form.
                        TextEntry::make('desaUnit.nama')
                            ->label(fn (User $record): string => $record->desa?->jenisSubUnit?->nama ?: 'Wilayah')
                            ->placeholder('—'),
                    ]),

                Section::make('Kontak')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->label('No. HP')
                            ->placeholder('—'),
                    ]),

                Section::make('Akun & Status')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('initial_otp')
                            ->label('OTP awal')
                            ->badge()
                            ->color('warning')
                            ->placeholder('—'),
                    ]),

                Section::make('Aktivitas')
                    ->columnSpanFull()
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
