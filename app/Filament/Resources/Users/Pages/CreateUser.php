<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * nagari_admin tidak melihat field nagari — akun yang dibuat dipaksa
     * ke nagarinya sendiri (warga/umkm_owner). Akun portal (warga/umkm)
     * memakai sandi awal OTP otomatis (wajib diganti saat login pertama).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if ($actor->isNagariAdmin()) {
            $data['nagari_id'] = $actor->nagari_id;
        }

        // super_admin (global) tidak terikat nagari.
        if (($data['role'] ?? null) === 'super_admin') {
            $data['nagari_id'] = null;
        }

        // Akun portal: sandi awal = OTP (di-hash via cast), wajib diganti.
        if (in_array($data['role'] ?? null, ['warga', 'umkm_owner'], true)) {
            $otp = User::generateOtp();
            $data['password'] = $otp;
            $data['initial_otp'] = $otp;
            $data['otp_expires_at'] = now()->addDays(User::OTP_TTL_DAYS);
            $data['must_change_password'] = true;
        }

        return $data;
    }

    /** Tampilkan OTP awal ke admin agar bisa disampaikan ke warga. */
    protected function afterCreate(): void
    {
        if ($this->record->isPortalAccount() && filled($this->record->initial_otp)) {
            Notification::make()
                ->title('Akun dibuat — OTP awal')
                ->body("NIK {$this->record->username} · OTP: {$this->record->initial_otp}. Sampaikan ke warga; wajib diganti saat login pertama.")
                ->success()
                ->persistent()
                ->send();
        }
    }
}
