<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Filament\Resources\Users\UserResource;
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
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $actor = auth()->user();
        $isSuperAdmin = $actor?->isSuperAdmin() ?? false;

        // Kolom & label "Wilayah" pakai sebutan sub-unit yang diatur per desa (Jorong/
        // Korong/Dusun). Untuk super_admin (lintas-desa) pakai istilah umum "Wilayah".
        $wilayahLabel = $isSuperAdmin
            ? 'Wilayah'
            : ($actor?->desa?->jenisSubUnit?->nama ?: 'Wilayah');

        // Filter Desa hanya untuk super_admin (warga desa_admin sudah ter-scope).
        $filters = [
            SelectFilter::make('status')
                ->label('Status')
                ->options(ActiveStatus::class),
            TrashedFilter::make(),
        ];

        if ($isSuperAdmin) {
            array_unshift(
                $filters,
                SelectFilter::make('desa')
                    ->label('Desa')
                    ->relationship('desa', 'nama')
                    ->searchable()
                    ->preload(),
            );
        }

        return $table
            // Klik baris membuka detail (aksi "Lihat" tak perlu lagi).
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('desaUnit.nama')
                    ->label($wilayahLabel)
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('desa.nama')
                    ->label('Desa')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->visible($isSuperAdmin),

                IconColumn::make('umkm_access_granted_at')
                    ->label('Akses UMKM')
                    // Paksa jadi boolean asli — kalau dibiarkan null, boolean() tak menggambar
                    // ikon apa pun. Dengan getStateUsing: true → centang hijau, false → silang abu.
                    ->getStateUsing(fn (User $record): bool => $record->hasUmkmAccess())
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (User $record): string => $record->hasUmkmAccess() ? 'Pemilik UMKM' : 'Belum diberi akses'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                // — Kolom tambahan (bisa dimunculkan lewat "Kolom") — default tersembunyi —
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone')
                    ->label('No. HP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('penduduk.jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn ($state): string => $state instanceof JenisKelamin ? $state->getLabel() : ($state ?: '—'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('penduduk.agama.nama')
                    ->label('Agama')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('penduduk.statusPerkawinan.nama')
                    ->label('Status Perkawinan')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('penduduk.pekerjaan.nama')
                    ->label('Pekerjaan')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('penduduk.tempat_lahir')
                    ->label('Tempat Lahir')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('penduduk.tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('initial_otp')
                    ->label('OTP awal')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->placeholder('—')
                    ->tooltip('Sandi sementara — warga wajib mengganti saat login pertama')
                    ->toggleable(isToggledHiddenByDefault: true),

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
            ->filters($filters)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('resetOtp')
                        ->label('Reset OTP')
                        ->icon('heroicon-o-key')
                        ->color('warning')
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
                        ->visible(fn (User $record): bool => ! $record->hasUmkmAccess())
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

                    DeleteAction::make(),
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
     * Cegah self-lockout pada aksi massal: batalkan bila akun sendiri ikut terpilih.
     * (Warga bukan akun sendiri, tapi guard dipertahankan sebagai jaring pengaman.)
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
