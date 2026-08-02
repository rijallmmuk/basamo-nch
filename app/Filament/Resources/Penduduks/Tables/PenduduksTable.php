<?php

namespace App\Filament\Resources\Penduduks\Tables;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\Penduduk;
use App\Services\InitialPasswordService;
use App\Services\UmkmService;
use App\Support\NagariContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenduduksTable
{
    public static function configure(Table $table): Table
    {
        $nagariId = auth()->user()?->managedNagariId(NagariContext::WARGA);
        $scopedToNagari = $nagariId !== null;

        $filters = [
            SelectFilter::make('account_status')
                ->label('Status Akun')
                ->options(ActiveStatus::class)
                ->query(fn (Builder $query, array $data): Builder => $query->when(
                    $data['value'] ?? null,
                    fn (Builder $builder, string $status): Builder => $builder->whereHas(
                        'user',
                        fn (Builder $account): Builder => $account->where('status', $status),
                    ),
                )),
            TrashedFilter::make(),
        ];

        if (! $scopedToNagari) {
            array_unshift(
                $filters,
                SelectFilter::make('nagari')
                    ->label('Nagari')
                    ->relationship('nagari', 'nama')
                    ->searchable()
                    ->preload(),
            );
        }

        return $table
            ->recordUrl(fn (Penduduk $record): string => PendudukResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->icon(fn (Penduduk $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily(FontFamily::Mono)
                    ->icon('heroicon-m-identification')
                    ->iconColor('gray'),

                TextColumn::make('jenis_kelamin')
                    ->label('L/P')
                    ->formatStateUsing(fn ($state): string => $state instanceof JenisKelamin ? $state->getLabel() : ($state ?: '—'))
                    ->badge()
                    ->color(fn ($state): string => $state instanceof JenisKelamin && $state->value === 'L' ? 'info' : 'danger')
                    ->alignCenter(),

                TextColumn::make('tanggal_lahir')
                    ->label('Usia')
                    ->formatStateUsing(fn (Penduduk $record): string => $record->tanggal_lahir ? $record->tanggal_lahir->age.' thn' : '—')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $realDirection = $direction === 'asc' ? 'desc' : 'asc';

                        return $query->orderBy('tanggal_lahir', $realDirection);
                    }),

                TextColumn::make('pekerjaan.nama')
                    ->label('Pekerjaan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('pendidikan.nama')
                    ->label('Pendidikan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('user.phone')
                    ->label('Kontak (HP & Email)')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->placeholder('Belum ada kontak')
                    ->description(fn (Penduduk $record): ?string => $record->user?->email),

                TextColumn::make('nagari.nama')
                    ->label('Nagari')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visible(! $scopedToNagari),

                IconColumn::make('user.umkm_access_granted_at')
                    ->label('Akses UMKM')
                    ->getStateUsing(fn (Penduduk $record): bool => $record->user?->hasUmkmAccess() ?? false)
                    ->boolean()
                    ->trueIcon('heroicon-m-building-storefront')
                    ->falseIcon('heroicon-m-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                TextColumn::make('user.status')
                    ->label('Status Akun')
                    ->badge()
                    ->sortable(),

                TextColumn::make('agama.nama')->label('Agama')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Didaftarkan Pada')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters($filters)
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->color('warning'),
                    Action::make('resetInitialPassword')
                        ->label('Reset password awal')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->authorize('update')
                        ->visible(fn (Penduduk $record): bool => ! $record->trashed()
                            && $record->user !== null
                            && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Reset password warga?')
                        ->modalDescription('Password lama tidak berlaku lagi dan warga wajib menggantinya setelah login.')
                        ->modalSubmitActionLabel('Reset password')
                        ->action(function (Penduduk $record): void {
                            app(InitialPasswordService::class)->apply($record->user);

                            Notification::make()
                                ->title('Password warga direset')
                                ->body("NIK {$record->nik}. Gunakan password awal warga dan wajib ganti setelah login.")
                                ->success()
                                ->persistent()
                                ->send();
                        }),
                    Action::make('beriAksesUmkm')
                        ->label('Beri Akses Kelola UMKM')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->authorize('update')
                        ->visible(fn (Penduduk $record): bool => ! $record->trashed()
                            && $record->user !== null
                            && ! $record->user->hasUmkmAccess()
                            && ! $record->user->umkmProfile()->onlyTrashed()->exists()
                            && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Beri akses kelola UMKM?')
                        ->modalDescription(fn (Penduduk $record): string => $record->user?->umkmProfile
                            ? 'Warga dapat kembali mengelola UMKM dan lapaknya akan diaktifkan kembali di katalog publik.'
                            : 'Warga akan melihat menu Kelola UMKM dan diminta mengisi profil usahanya sendiri saat pertama masuk.')
                        ->modalSubmitActionLabel('Beri Akses')
                        ->action(function (Penduduk $record): void {
                            app(UmkmService::class)->grantAccess($record->user);
                            Notification::make()
                                ->title('Akses UMKM diberikan')
                                ->body('Warga dapat mengisi profil usahanya sendiri setelah masuk.')
                                ->success()
                                ->send();
                        }),
                    Action::make('cabutAksesUmkm')
                        ->label('Cabut Akses UMKM')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->authorize('update')
                        ->visible(fn (Penduduk $record): bool => ! $record->trashed()
                            && $record->user !== null
                            && $record->user->hasUmkmAccess()
                            && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Cabut hak akses UMKM?')
                        ->modalDescription('Warga tidak akan bisa lagi mengakses menu UMKM. Lapaknya otomatis akan disembunyikan dari publik.')
                        ->modalSubmitActionLabel('Cabut Akses')
                        ->action(function (Penduduk $record): void {
                            app(UmkmService::class)->revokeAccess($record->user);
                            Notification::make()->title('Akses UMKM dicabut')->success()->send();
                        }),
                    Action::make('aktifkanAkun')
                        ->label('Aktifkan Akun')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->authorize('update')
                        ->visible(fn (Penduduk $record): bool => ! $record->trashed()
                            && $record->user !== null
                            && $record->user->status === ActiveStatus::Inactive
                            && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan akun warga?')
                        ->modalDescription(fn (Penduduk $record): string => $record->user?->hasUmkmAccess()
                            && $record->user->umkmProfile
                            ? 'Warga dapat kembali login dan lapak UMKM-nya akan diaktifkan kembali di katalog publik.'
                            : 'Warga akan bisa kembali login ke dalam portal.')
                        ->modalSubmitActionLabel('Aktifkan')
                        ->action(function (Penduduk $record): void {
                            $record->user->update(['status' => ActiveStatus::Active]);
                            Notification::make()->title('Akun warga diaktifkan')->success()->send();
                        }),
                    Action::make('nonaktifkanAkun')
                        ->label('Nonaktifkan Akun')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->authorize('update')
                        ->visible(fn (Penduduk $record): bool => ! $record->trashed()
                            && $record->user !== null
                            && $record->user->status === ActiveStatus::Active
                            && auth()->user()->can('update', $record))
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan akun warga?')
                        ->modalDescription('Warga tidak akan bisa login ke dalam portal. Jika memiliki lapak UMKM, lapak tersebut akan disembunyikan.')
                        ->modalSubmitActionLabel('Nonaktifkan')
                        ->action(function (Penduduk $record): void {
                            $record->user->update(['status' => ActiveStatus::Inactive]);
                            Notification::make()->title('Akun warga dinonaktifkan')->success()->send();
                        }),
                    Action::make('lihatUmkm')
                        ->label('Lihat UMKM')
                        ->icon('heroicon-o-building-storefront')
                        ->color('info')
                        ->visible(fn (Penduduk $record): bool => $record->user?->hasUmkmAccess() === true
                            && $record->user->umkmProfile !== null
                            && (auth()->user()?->can('view', $record->user->umkmProfile) ?? false))
                        ->url(fn (Penduduk $record): string => UmkmProfileResource::getUrl(
                            'view',
                            ['record' => $record->user->umkmProfile],
                        )),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->modalDescription('Identitas, akun, progres belajar, diskusi, serta lapak UMKM warga ini dihapus permanen.')
                        ->before(fn (ForceDeleteAction $action, Penduduk $record) => PendudukResource::guardAgainstThirdPartyDiscussions($record, $action)),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
