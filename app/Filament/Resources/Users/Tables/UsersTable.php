<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    private const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'nagari_admin' => 'Admin Nagari',
        'warga' => 'Warga',
    ];

    private const ROLE_COLORS = [
        'super_admin' => 'danger',
        'nagari_admin' => 'warning',
        'warga' => 'info',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('initial_otp')
                    ->label('OTP awal')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->placeholder('—')
                    ->tooltip('Sandi sementara — warga wajib mengganti saat login pertama')
                    ->toggleable(),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::ROLE_LABELS[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => self::ROLE_COLORS[$state] ?? 'gray')
                    ->sortable(),

                TextColumn::make('umkm_access_granted_at')
                    ->label('Akses UMKM')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (): string => 'Pemilik UMKM')
                    ->color('success')
                    ->icon('heroicon-o-building-storefront')
                    ->toggleable(),

                TextColumn::make('nagari.nama')
                    ->label('Nagari')
                    ->default('Global')
                    ->icon(fn ($state): ?string => $state === 'Global' ? 'heroicon-o-globe-alt' : null)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('wilayah.nama')
                    ->label('Wilayah')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Nonaktif')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('total_xp')
                    ->label('XP')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(self::ROLE_LABELS),

                SelectFilter::make('nagari')
                    ->label('Nagari')
                    ->relationship('nagari', 'nama')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif']),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('resetOtp')
                    ->label('Reset OTP')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (User $record): bool => $record->isPortalAccount())
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan OTP baru')
                    ->modalDescription('Sandi lama warga tidak berlaku lagi. Warga login dengan OTP baru lalu wajib menggantinya.')
                    ->action(function (User $record): void {
                        $otp = $record->issueOtp();

                        Notification::make()
                            ->title('OTP baru diterbitkan')
                            ->body("NIK {$record->username} · OTP: {$otp}. Sampaikan ke warga.")
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Action::make('beriAksesUmkm')
                    ->label('Beri akses UMKM')
                    ->icon('heroicon-o-building-storefront')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->role === 'warga' && ! $record->hasUmkmAccess())
                    ->requiresConfirmation()
                    ->modalHeading('Beri akses UMKM')
                    ->modalDescription('Warga ini dapat mengisi profil usaha & mengelola produk di portal ("Produk Saya"). Akses belajar tetap ada.')
                    ->action(function (User $record): void {
                        $record->update(['umkm_access_granted_at' => now()]);

                        Notification::make()
                            ->title('Akses UMKM diberikan')
                            ->body($record->name.' kini bisa mengelola UMKM.')
                            ->success()
                            ->send();
                    }),

                Action::make('cabutAksesUmkm')
                    ->label('Cabut akses UMKM')
                    ->icon('heroicon-o-building-storefront')
                    ->color('warning')
                    ->visible(fn (User $record): bool => $record->hasUmkmAccess())
                    ->requiresConfirmation()
                    ->modalHeading('Cabut akses UMKM')
                    ->modalDescription('Warga tidak lagi bisa mengelola UMKM. Profil usahanya dinonaktifkan (keluar dari katalog publik); data tetap tersimpan dan bisa diaktifkan lagi bila akses dipulihkan.')
                    ->action(function (User $record): void {
                        $record->update(['umkm_access_granted_at' => null]);

                        // Nonaktifkan lapaknya agar tak jadi konten publik yang tak terkelola.
                        $record->umkmProfile?->update(['status' => 'inactive']);

                        Notification::make()
                            ->title('Akses UMKM dicabut')
                            ->body($record->name.' tidak lagi mengelola UMKM. Lapaknya dinonaktifkan.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()
                    // Tak boleh menghapus akun sendiri (cegah self-lockout).
                    ->visible(fn (User $record): bool => $record->getKey() !== auth()->id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
