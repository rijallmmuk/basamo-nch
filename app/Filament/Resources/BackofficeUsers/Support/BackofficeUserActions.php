<?php

namespace App\Filament\Resources\BackofficeUsers\Support;

use App\Models\User;
use App\Services\InitialPasswordService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Aksi akun back-office yang dipakai bersama tabel dan halaman detail, supaya
 * kata-katanya persis sama di keduanya.
 *
 * Reset password sudah lama ada untuk warga (tabel Warga) dan operator (halaman
 * Nagari), tetapi tidak untuk akun panel lain. Akibatnya superadmin, pengajar,
 * atau DPMD yang lupa sandinya tidak punya jalan pulih sama sekali: form Ubah
 * hanya bisa MENGGANTI sandi menjadi nilai yang diketik, bukan mengembalikannya
 * ke sandi awal peran yang bersangkutan.
 */
class BackofficeUserActions
{
    public static function resetInitialPassword(): Action
    {
        return Action::make('resetInitialPassword')
            ->label('Reset password')
            ->icon('heroicon-o-key')
            ->color('danger')
            ->authorize('update')
            // Akun sendiri sengaja dikecualikan: menyetel wajib-ganti pada sesi yang
            // sedang berjalan langsung mengunci pemakainya ke modal ganti sandi.
            ->visible(fn (User $record): bool => (auth()->user()?->isSuperAdmin() ?? false)
                && ! $record->trashed()
                && $record->getKey() !== auth()->id())
            ->requiresConfirmation()
            ->modalHeading('Reset password akun?')
            ->modalDescription('Password lama tidak berlaku lagi. Pemilik akun masuk memakai password awal perannya dan wajib menggantinya setelah login.')
            ->modalIcon('heroicon-o-key')
            ->modalSubmitActionLabel('Reset password')
            ->action(function (User $record): void {
                app(InitialPasswordService::class)->apply($record);

                Notification::make()
                    ->title('Password akun direset')
                    ->body("Akun {$record->name}. Gunakan password awal perannya dan wajib ganti setelah login.")
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
