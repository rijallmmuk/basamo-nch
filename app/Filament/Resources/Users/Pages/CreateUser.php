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
     * desa_admin tidak melihat field desa — akun yang dibuat dipaksa
     * ke desanya sendiri (warga). Akun portal (warga) memakai sandi awal
     * OTP otomatis (wajib diganti saat login pertama).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if ($actor->isDesaAdmin()) {
            $data['desa_id'] = $actor->desa_id;
            // Guard server-side (tak bergantung enforcement opsi Select):
            // desa_admin hanya boleh membuat warga, bukan admin.
            $data['role'] = 'warga';
        }

        // super_admin (global) tidak terikat desa.
        if (($data['role'] ?? null) === 'super_admin') {
            $data['desa_id'] = null;
        }

        // Akun portal: sandi awal = OTP (di-hash via cast), wajib diganti.
        if (($data['role'] ?? null) === 'warga') {
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
