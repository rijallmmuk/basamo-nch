<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\WargaProvisioningService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Setiap akun di resource ini = warga. desa_admin → warga ke desanya sendiri;
     * super_admin → desa dipilih di form. Provisioning (peran, OTP, penduduk) di service.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Desa konteks (desa_admin → desanya; super admin → desa yang dikelola).
        $desaId = auth()->user()?->managedDesaId() ?? (int) ($data['desa_id'] ?? 0);

        return app(WargaProvisioningService::class)->create($data, $desaId);
    }

    /** Beri tahu admin status OTP-nya. */
    protected function afterCreate(): void
    {
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
