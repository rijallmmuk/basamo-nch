<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\ActiveStatus;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
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

    public static function configure(Table $table): Table
    {
        // Admin desa hanya kelola warga di desanya → kolom/filter Peran & Desa tak relevan.
        $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nik')
                    ->label('NIK / Username')
                    ->state(fn ($record): ?string => $record->nik ?? $record->username)
                    ->searchable(['nik', 'username'])
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
                    ->sortable()
                    ->visible($isSuperAdmin),

                TextColumn::make('umkm_access_granted_at')
                    ->label('Akses UMKM')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (): string => 'Pemilik UMKM')
                    ->color('success')
                    ->icon('heroicon-o-building-storefront')
                    ->toggleable(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->default('Global')
                    ->icon(fn ($state): ?string => $state === 'Global' ? 'heroicon-o-globe-alt' : null)
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->visible($isSuperAdmin),

                TextColumn::make('desaUnit.nama')
                    ->label('Wilayah')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
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
            ->filters(array_values(array_filter([
                $isSuperAdmin ? SelectFilter::make('role')
                    ->label('Peran')
                    ->options(self::ROLE_LABELS) : null,

                $isSuperAdmin ? SelectFilter::make('desa')
                    ->label('Desa')
                    ->relationship('desa', 'nama')
                    ->searchable()
                    ->preload() : null,

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ActiveStatus::class),

                TrashedFilter::make(),
            ])))
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

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
                                ->body("NIK {$record->nik} · OTP: {$otp}. Sampaikan ke warga.")
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
                            $record->umkmProfile?->update(['status' => ActiveStatus::Inactive]);

                            Notification::make()
                                ->title('Akses UMKM dicabut')
                                ->body($record->name.' tidak lagi mengelola UMKM. Lapaknya dinonaktifkan.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        // Tak boleh menghapus akun sendiri (cegah self-lockout).
                        ->visible(fn (User $record): bool => $record->getKey() !== auth()->id()),
                ])->tooltip('Aksi'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(self::guardSelfInBulk()),
                    ForceDeleteBulkAction::make()
                        ->before(self::guardSelfInBulk()),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Cegah self-lockout pada aksi massal: batalkan bila akun sendiri ikut terpilih
     * (DeleteAction baris tunggal sudah menyembunyikan diri sendiri).
     */
    private static function guardSelfInBulk(): Closure
    {
        return function (Collection $records, BulkAction $action): void {
            if ($records->contains(fn (User $record): bool => $record->getKey() === auth()->id())) {
                Notification::make()
                    ->title('Tidak bisa menghapus akun sendiri')
                    ->body('Lepaskan centang pada akun Anda sebelum menghapus massal.')
                    ->danger()
                    ->send();

                $action->halt();
            }
        };
    }
}
