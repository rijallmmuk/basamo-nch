<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Models\Desa;
use App\Models\User;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $actor = auth()->user();

        // Desa konteks (desa_admin → desanya; super admin → desa yang dikelola).
        // Saat ter-scope ke satu desa, tabel berperilaku identik dengan panel admin
        // desa: tanpa kolom/filter Desa, label sub-unit ikut sebutan desa tsb.
        $desaId = $actor?->managedDesaId();
        $scopedToDesa = $desaId !== null;
        $managedDesa = $desaId ? Desa::find($desaId) : null;

        $wilayahLabel = $managedDesa?->jenisSubUnit?->nama ?: 'Wilayah';

        $filters = [
            SelectFilter::make('status')
                ->label('Status')
                ->options(ActiveStatus::class),
            TrashedFilter::make(),
        ];

        // Filter Desa hanya bila tak ter-scope (super admin tanpa konteks).
        if (! $scopedToDesa) {
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
            // Baris TIDAK dapat diklik — buka Ubah lewat aksi di menu ⋮.
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    // Penanda baris terhapus — terlihat sekilas saat filter "termasuk terhapus" aktif.
                    ->icon(fn (User $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (User $record): ?string => $record->trashed() ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y') : null),

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
                    ->alignCenter()
                    ->visible(! $scopedToDesa),

                IconColumn::make('umkm_access_granted_at')
                    ->label('Akses UMKM')
                    // Paksa jadi boolean asli — kalau dibiarkan null, boolean() tak menggambar
                    // ikon apa pun. Dengan getStateUsing: true → centang hijau, false → silang abu.
                    ->getStateUsing(fn (User $record): bool => $record->hasUmkmAccess())
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(fn (User $record): string => $record->hasUmkmAccess() ? 'Pemilik UMKM' : 'Belum diberi akses'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                // Tampil default agar admin bisa langsung melihat/menyalin OTP yang masih
                // tertunda. Kosong (—) berarti warga sudah login & mengganti sandi sendiri.
                TextColumn::make('initial_otp')
                    ->label('OTP awal')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->placeholder('—')
                    ->tooltip('Sandi sementara — warga wajib mengganti saat login pertama')
                    ->toggleable()
                    ->alignCenter(),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                TextColumn::make('total_xp')
                    ->label('XP')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->filters($filters)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('warning'),

                    Action::make('resetOtp')
                        ->label('Reset OTP')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        // Hanya untuk warga aktif (bukan yang dihapus) & yang boleh dikelola.
                        ->visible(fn (User $record): bool => ! $record->trashed() && auth()->user()->can('update', $record))
                        ->modalHeading('Terbitkan OTP baru')
                        ->modalDescription('Sandi lama warga tidak berlaku lagi. Warga login dengan OTP baru lalu wajib menggantinya.')
                        ->modalIcon('heroicon-o-key')
                        ->modalSubmitActionLabel('Terbitkan')
                        ->schema([
                            // Admin boleh menetapkan kode sendiri; dikosongkan → 6 digit otomatis.
                            TextInput::make('otp')
                                ->label('Kode OTP')
                                ->helperText('Kosongkan untuk membuat kode 6 digit otomatis.')
                                ->minLength(4)
                                ->maxLength(12),
                        ])
                        ->action(function (User $record, array $data): void {
                            $otp = $record->issueOtp($data['otp'] ?? null);

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
                        ->visible(fn (User $record): bool => ! $record->trashed() && ! $record->hasUmkmAccess() && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Beri akses UMKM')
                        ->modalDescription('Warga ini dapat mengisi profil usaha & mengelola produk di portal ("Produk Saya"). Akses belajar tetap ada.')
                        ->action(function (User $record): void {
                            // Warga dgn pengajuan berjalan → jalurnya = persetujuan
                            // pengajuan (lapak & produk ikut tayang + notif keputusan),
                            // agar tak ada state "punya akses tapi pengajuan menggantung".
                            $profile = $record->umkmProfile()->first();

                            if ($profile?->status_pengajuan !== null) {
                                app(UmkmService::class)->approveApplication($profile, auth()->user());
                            } else {
                                // Pulihkan sekalian lapak yang dinonaktifkan saat akses
                                // dicabut — sesuai janji modal cabut ("bisa diaktifkan
                                // lagi bila akses dipulihkan").
                                DB::transaction(function () use ($record, $profile): void {
                                    $record->update(['umkm_access_granted_at' => now()]);
                                    $profile?->update(['status' => ActiveStatus::Active]);
                                });
                            }

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
                        ->visible(fn (User $record): bool => ! $record->trashed() && $record->hasUmkmAccess() && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Cabut akses UMKM')
                        ->modalDescription('Warga tidak lagi bisa mengelola UMKM. Profil usahanya dinonaktifkan (keluar dari katalog publik); data tetap tersimpan dan bisa diaktifkan lagi bila akses dipulihkan.')
                        ->action(function (User $record): void {
                            // Atomik: cabut akses + nonaktifkan lapak harus berhasil bersama.
                            DB::transaction(function () use ($record): void {
                                $record->update(['umkm_access_granted_at' => null]);

                                // Nonaktifkan lapaknya agar tak jadi konten publik yang tak terkelola.
                                $record->umkmProfile?->update(['status' => ActiveStatus::Inactive]);
                            });

                            Notification::make()
                                ->title('Akses UMKM dicabut')
                                ->body($record->name.' tidak lagi mengelola UMKM. Lapaknya dinonaktifkan.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->modalDescription('Seluruh data akun ini (progres belajar, XP, diskusi, lapak UMKM beserta fotonya) ikut terhapus permanen dan tidak bisa dikembalikan. Identitas kependudukannya tetap tersimpan.'),
                ])
                    ->icon('heroicon-m-squares-2x2')
                    ->tooltip('Aksi'),
            ])
            // Tanpa aksi massal → tak ada checkbox pilih baris. Hapus/pulihkan/hapus
            // permanen per-baris tersedia via menu ⋮ dan halaman Edit.
            // Opsi "Semua" aman di sini: warga ter-scope per desa (desa_admin) dan
            // jumlah penduduk satu nagari terbatas.
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultSort('created_at', 'desc');
    }
}
