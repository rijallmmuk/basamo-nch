<?php

namespace App\Filament\Resources\Penduduks\Pages;

use App\Enums\ActiveStatus;
use App\Filament\Resources\Penduduks\Concerns\BelongsToNagariContext;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\Penduduk;
use App\Services\InitialPasswordService;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPenduduk extends ViewRecord
{
    use BelongsToNagariContext;

    protected static string $resource = PendudukResource::class;

    public function getTitle(): string
    {
        return 'Detail Warga · '.$this->record->nama;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Edit Warga')->color('warning'),

            ActionGroup::make([
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
                        && (auth()->user()?->can('update', $record->user->umkmProfile) ?? false))
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
                ->label('Aksi Warga')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }
}
