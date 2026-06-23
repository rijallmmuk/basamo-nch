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
     * Setiap akun di resource ini = warga. desa_admin → warga ke desanya sendiri;
     * super_admin → desa dipilih di form. OTP tidak otomatis (lihat di bawah).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        // Resource khusus warga (akun admin dibuat lewat alur lain).
        $data['role'] = 'warga';

        // desa_admin: warga dipaksa ke desanya. super_admin: desa dipilih di form.
        if ($actor->isDesaAdmin()) {
            $data['desa_id'] = $actor->desa_id;
        }

        // OTP TIDAK di-generate otomatis. Bila admin mengisi OTP manual, itu jadi sandi
        // awal; bila kosong, akun dibuat tanpa OTP (sandi acak tak terpakai) — OTP
        // diterbitkan nanti lewat aksi "Reset OTP" saat warga siap login.
        $otp = $data['initial_otp'] ?? null;

        if (filled($otp)) {
            $data['password'] = $otp;
            $data['initial_otp'] = $otp;
        } else {
            $data['password'] = Str::random(40); // tak terpakai sampai OTP diterbitkan
            $data['initial_otp'] = null;
        }

        $data['must_change_password'] = true;

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
