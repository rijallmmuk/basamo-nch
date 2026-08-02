<?php

namespace App\Filament\Resources\UmkmProfiles\Support;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;

/**
 * Aksi lapak yang dipakai bersama tabel UMKM dan halaman detailnya, supaya
 * pengelola melihat pilihan dan kata-kata yang persis sama di kedua tempat.
 *
 * Tiga tindakan yang sengaja dibedakan tegas:
 *
 *  - Nonaktifkan  : sementara, lapak keluar dari katalog publik, pemilik tetap
 *                   dapat mengelola isinya. Paling ringan, mudah dibalik.
 *                   WEWENANG OPERATOR, bukan pemilik lapak.
 *  - Arsipkan     : lapak disimpan tapi disingkirkan DAN akses kelola pemilik
 *                   dicabut. Masih bisa dipulihkan berikut produknya.
 *  - Hapus permanen: lapak, seluruh produk, dan semua fotonya hilang selamanya.
 */
class UmkmProfileActions
{
    /**
     * Buka/tutup lapak dari katalog publik tanpa menyentuh hak kelola pemilik.
     * Hanya untuk operator/superadmin: pemilik tidak menentukan sendiri kapan
     * lapaknya tayang.
     */
    public static function toggleStatus(): Action
    {
        return Action::make('toggleStatus')
            ->label(fn (UmkmProfile $record): string => $record->status === ActiveStatus::Active
                ? 'Nonaktifkan Lapak'
                : 'Aktifkan Lapak')
            ->icon(fn (UmkmProfile $record): string => $record->status === ActiveStatus::Active
                ? 'heroicon-o-eye-slash'
                : 'heroicon-o-eye')
            ->color(fn (UmkmProfile $record): string => $record->status === ActiveStatus::Active
                ? 'warning'
                : 'success')
            ->authorize(fn (UmkmProfile $record): bool => ! UmkmProfileResource::isSelfService()
                && (auth()->user()?->can('delete', $record) ?? false))
            ->visible(fn (UmkmProfile $record): bool => ! $record->trashed()
                && ! UmkmProfileResource::isSelfService()
                && (auth()->user()?->can('delete', $record) ?? false))
            ->requiresConfirmation()
            ->modalHeading(fn (UmkmProfile $record): string => $record->status === ActiveStatus::Active
                ? "Nonaktifkan lapak \"{$record->nama_usaha}\"?"
                : "Aktifkan lapak \"{$record->nama_usaha}\"?")
            ->modalDescription(fn (UmkmProfile $record): string => $record->status === ActiveStatus::Active
                ? 'Lapak beserta seluruh produknya keluar dari katalog publik. Data dan akses kelola pemilik tidak berubah, dan dapat diaktifkan lagi kapan saja.'
                : 'Lapak beserta seluruh produknya kembali tayang di katalog publik.')
            ->modalSubmitActionLabel(fn (UmkmProfile $record): string => $record->status === ActiveStatus::Active
                ? 'Ya, nonaktifkan'
                : 'Ya, aktifkan')
            ->action(function (UmkmProfile $record): void {
                $aktif = $record->status !== ActiveStatus::Active;

                app(UmkmService::class)->setProfileStatus(
                    $record,
                    $aktif ? ActiveStatus::Active : ActiveStatus::Inactive,
                );

                Notification::make()
                    ->title($aktif ? 'Lapak diaktifkan' : 'Lapak dinonaktifkan')
                    ->body($aktif
                        ? "\"{$record->nama_usaha}\" kembali tayang di katalog publik."
                        : "\"{$record->nama_usaha}\" disembunyikan dari katalog publik.")
                    ->success()
                    ->send();
            });
    }

    public static function archive(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Arsipkan Lapak')
            ->icon('heroicon-o-archive-box')
            ->modalHeading(fn (UmkmProfile $record): string => "Arsipkan lapak \"{$record->nama_usaha}\"?")
            ->modalDescription('Lapak disingkirkan dari katalog publik DAN akses kelola pemilik dicabut. Produk tetap tersimpan dan dapat dipulihkan bersama lapaknya. Untuk sekadar menutup sementara, pakai Nonaktifkan.')
            ->modalSubmitActionLabel('Ya, arsipkan')
            ->successNotificationTitle('Lapak diarsipkan');
    }

    public static function restore(): RestoreAction
    {
        return RestoreAction::make()
            ->label('Pulihkan Lapak')
            ->modalHeading(fn (UmkmProfile $record): string => "Pulihkan lapak \"{$record->nama_usaha}\"?")
            ->modalDescription('Lapak beserta produknya dipulihkan. Akses kelola pemilik diberikan kembali dan lapak kembali tayang di katalog publik.')
            ->modalSubmitActionLabel('Ya, pulihkan')
            ->successNotificationTitle('Lapak dipulihkan');
    }

    public static function forceDelete(): ForceDeleteAction
    {
        return ForceDeleteAction::make()
            ->label('Hapus Permanen')
            ->modalHeading(fn (UmkmProfile $record): string => "Hapus permanen lapak \"{$record->nama_usaha}\"?")
            ->modalDescription('Lapak, seluruh produk, dan semua fotonya dihapus selamanya. Akses kelola pemilik tetap dicabut. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, hapus permanen')
            ->successNotificationTitle('Lapak dihapus permanen');
    }
}
