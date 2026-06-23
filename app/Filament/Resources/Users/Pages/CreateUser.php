<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\InteractsWithPenduduk;
use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

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

        // Akun portal: OTP TIDAK di-generate otomatis. Bila admin mengisi OTP manual,
        // itu jadi sandi awal; bila kosong, akun dibuat tanpa OTP (sandi acak tak terpakai)
        // dan OTP diterbitkan nanti lewat aksi "Reset OTP" saat warga siap login.
        if (($data['role'] ?? null) === 'warga') {
            $otp = $data['initial_otp'] ?? null;

            if (filled($otp)) {
                $data['password'] = $otp;
                $data['initial_otp'] = $otp;
            } else {
                $data['password'] = Str::random(40); // tak terpakai sampai OTP diterbitkan
                $data['initial_otp'] = null;
            }

            $data['must_change_password'] = true;
        }

        // Pisahkan field identitas → disimpan ke `penduduk` di afterCreate().
        return $this->extractPendudukData($data);
    }

    /** Simpan identitas penduduk lalu beri tahu admin status OTP-nya. */
    protected function afterCreate(): void
    {
        $this->syncPenduduk();

        if (! $this->record->isPortalAccount()) {
            return;
        }

        if (filled($this->record->initial_otp)) {
            Notification::make()
                ->title('Akun dibuat — OTP awal')
                ->body("NIK {$this->record->nik} · OTP: {$this->record->initial_otp}. Sampaikan ke warga; wajib diganti saat login pertama.")
                ->success()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Warga dibuat — OTP belum diterbitkan')
            ->body("NIK {$this->record->nik}. Terbitkan OTP lewat aksi \"Reset OTP\" saat warga siap login.")
            ->info()
            ->persistent()
            ->send();
    }
}
