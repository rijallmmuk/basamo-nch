<?php

namespace App\Filament\Resources\Desas\Tables;

use App\Enums\ActiveStatus;
use App\Filament\Resources\Desas\DesaResource;
use App\Filament\Resources\DesaUnits\DesaUnitResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Desa;
use App\Support\DesaContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DesasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Baris TIDAK dapat diklik — buka Ubah lewat aksi di menu ⋮.
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->formatStateUsing(fn (Desa $record): string => $record->nama_lengkap)
                    ->searchable()
                    ->sortable()
                    // Penanda baris terhapus — terlihat sekilas saat filter "termasuk terhapus" aktif.
                    ->icon(fn (Desa $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (Desa $record): ?string => $record->trashed() ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y') : null),

                TextColumn::make('wilayah_kode')
                    ->label('Kode Wilayah')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    // Tampil dgn titik (mis. 13.71.01.1001) tapi yang tersalin hanya angka.
                    ->copyableState(fn (?string $state): string => preg_replace('/\D/', '', (string) $state))
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('kabupaten')
                    ->label('Kabupaten/Kota')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('warga_count')
                    ->label('Warga')
                    ->counts('warga')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                // Login admin desa = kode nagari (paralel kolom NIK warga).
                TextColumn::make('desaAdmin.username')
                    ->label('Username Admin')
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                // OTP awal admin yang masih tertunda (paralel kolom "OTP awal" warga).
                // Kosong (—) berarti admin sudah login & ganti sandi, atau OTP belum terbit.
                TextColumn::make('desaAdmin.initial_otp')
                    ->label('OTP Admin')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->placeholder('—')
                    ->tooltip('Sandi sementara admin — wajib diganti saat login pertama')
                    ->toggleable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ActiveStatus::class),

                TrashedFilter::make(),
            ])
            // Semua aksi baris dalam satu menu ⋮ (pola sama dgn halaman Warga). "Kelola"
            // = pintu masuk konteks desa (DesaContext) ke resource yang dipakai-ulang dari
            // panel admin desa. Baris tak dapat diklik → Ubah lewat aksi di menu ini.
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('warning'),

                    Action::make('kelolaWarga')
                        ->label('Kelola Warga')
                        ->icon('heroicon-o-users')
                        ->action(function (Desa $record) {
                            DesaContext::set($record->getKey());

                            return redirect(UserResource::getUrl('index'));
                        }),

                    Action::make('kelolaWilayah')
                        ->label('Kelola Wilayah')
                        ->icon('heroicon-o-map-pin')
                        ->action(function (Desa $record) {
                            DesaContext::set($record->getKey());

                            return redirect(DesaUnitResource::getUrl('index'));
                        }),

                    Action::make('kelolaUmkm')
                        ->label('Kelola UMKM')
                        ->icon('heroicon-o-building-storefront')
                        ->action(function (Desa $record) {
                            DesaContext::set($record->getKey());

                            return redirect(UmkmProfileResource::getUrl('index'));
                        }),

                    // Reset OTP admin — perilaku identik aksi "Reset OTP" warga
                    // (blank → 6 digit otomatis; isi → kustom). Muncul bila admin ada.
                    Action::make('resetOtpAdmin')
                        ->label('Reset OTP Admin')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn (Desa $record): bool => $record->desaAdmin()->exists())
                        ->modalHeading('Terbitkan OTP baru')
                        ->modalDescription('Sandi lama admin desa tidak berlaku lagi. Admin login dengan OTP baru lalu wajib menggantinya.')
                        ->modalIcon('heroicon-o-key')
                        ->modalSubmitActionLabel('Terbitkan')
                        ->schema([
                            TextInput::make('otp')
                                ->label('Kode OTP')
                                ->helperText('Kosongkan untuk membuat kode 6 digit otomatis.')
                                ->minLength(4)
                                ->maxLength(12),
                        ])
                        ->action(fn (Desa $record, array $data) => DesaResource::resetAdminOtp($record, $data['otp'] ?? null)),

                    // Hapus per-record agar guard anti-orphan (warga/modul) berjalan.
                    DeleteAction::make()
                        ->before(fn (Desa $record, DeleteAction $action) => DesaResource::guardAgainstDependents($record, $action))
                        ->after(fn (Desa $record) => DesaResource::archiveAdmin($record)),
                    RestoreAction::make()
                        // Kode wilayah desa terarsip boleh dipakai ulang desa baru — maka
                        // saat dipulihkan, pastikan kodenya belum dipakai desa aktif lain
                        // (unik komposit (kode, deleted_at) TIDAK menahan duplikat aktif
                        // di MariaDB; tanpa guard ini bisa lahir 2 desa aktif berkode sama
                        // dengan 2 admin ber-username sama → login ambigu).
                        ->before(function (Desa $record, RestoreAction $action): void {
                            $bentrok = Desa::where('wilayah_kode', $record->wilayah_kode)
                                ->whereKeyNot($record->getKey())
                                ->exists();

                            if ($bentrok) {
                                Notification::make()
                                    ->title('Tidak bisa dipulihkan')
                                    ->body('Kode wilayah desa ini sudah dipakai desa aktif lain. Hapus/ubah desa tersebut dulu.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        })
                        ->after(fn (Desa $record) => DesaResource::restoreAdmin($record)),
                    ForceDeleteAction::make()
                        ->before(function (Desa $record, ForceDeleteAction $action): void {
                            DesaResource::guardAgainstDependents($record, $action, includeTrashed: true);

                            // Harus SEBELUM desa lenyap: FK users.desa_id SET NULL saat
                            // desa dihapus permanen, sehingga relasi desaAdmin() putus
                            // dan akun admin (terarsip) tertinggal jadi yatim.
                            DesaResource::forceDeleteAdmin($record);
                        }),
                ])
                    ->icon('heroicon-m-squares-2x2')
                    ->tooltip('Aksi'),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultSort('nama');
    }
}
