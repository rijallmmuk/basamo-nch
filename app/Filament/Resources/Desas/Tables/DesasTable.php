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
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DesasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Klik baris → buka Edit (identitas desa).
            ->recordUrl(fn (Desa $record): string => DesaResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->formatStateUsing(fn (Desa $record): string => $record->nama_lengkap)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('wilayah_kode')
                    ->label('Kode Wilayah')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

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
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

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
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ActiveStatus::class),

                TrashedFilter::make(),
            ])
            // Semua aksi baris dalam satu menu ⋮ (pola sama dgn halaman Warga). "Kelola"
            // = pintu masuk konteks desa (DesaContext) ke resource yang dipakai-ulang dari
            // panel admin desa. Klik baris tetap membuka Edit (lihat recordUrl).
            ->recordActions([
                ActionGroup::make([
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
                        ->after(fn (Desa $record) => DesaResource::restoreAdmin($record)),
                    ForceDeleteAction::make()
                        ->before(fn (Desa $record, ForceDeleteAction $action) => DesaResource::guardAgainstDependents($record, $action, includeTrashed: true)),
                ])->tooltip('Aksi'),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultSort('nama');
    }
}
