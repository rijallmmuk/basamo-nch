<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\InteractsWithPenduduk;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use InteractsWithPenduduk;

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

        // Akun portal: sandi awal = OTP (di-hash via cast), wajib diganti. OTP boleh
        // diisi manual; kosong → otomatis. Tanpa kedaluwarsa; terhapus saat sandi diganti.
        if (($data['role'] ?? null) === 'warga') {
            $otp = filled($data['initial_otp'] ?? null) ? $data['initial_otp'] : User::generateOtp();
            $data['password'] = $otp;
            $data['initial_otp'] = $otp;
            $data['must_change_password'] = true;
        }

        // Pisahkan field identitas → disimpan ke `penduduk` di afterCreate().
        return $this->extractPendudukData($data);
    }

    /** Simpan identitas penduduk lalu tampilkan OTP awal ke admin. */
    protected function afterCreate(): void
    {
        $this->syncPenduduk();

        if ($this->record->isPortalAccount() && filled($this->record->initial_otp)) {
            Notification::make()
                ->title('Akun dibuat — OTP awal')
                ->body("NIK {$this->record->nik} · OTP: {$this->record->initial_otp}. Sampaikan ke warga; wajib diganti saat login pertama.")
                ->success()
                ->persistent()
                ->send();
        }
    }
}
