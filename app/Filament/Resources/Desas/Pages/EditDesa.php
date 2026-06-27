<?php

namespace App\Filament\Resources\Desas\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Desas\DesaResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditDesa extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = DesaResource::class;

    /** @var array{created: bool, otp: ?string}|null Hasil provisioning admin. */
    protected ?array $adminResult = null;

    /**
     * Isi field admin (`admin_*`, tak dehidrasi) dari akun admin yang ada. Username
     * tak diisi (otomatis dari kode); OTP direset lewat aksi di tabel Desa.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $admin = $this->record->desaAdmin()->first();

        // Username read-only ditampilkan dari admin yang ada, atau diturunkan dari kode.
        $data['admin_username_display'] = $admin?->username ?? $this->record->defaultAdminUsername();
        // OTP read-only: tampilkan OTP yang masih tertunda (bila ada); kosong = sudah
        // diganti / belum diterbitkan (placeholder field yang menjelaskan).
        $data['admin_otp_current'] = $admin?->initial_otp;
        $data['admin_email'] = $admin?->email;
        $data['admin_kontak'] = $admin?->phone;

        return $data;
    }

    /**
     * Perbarui desa + sinkron akun admin dalam satu transaksi (simetris dgn Create)
     * agar tak ada keadaan setengah-jadi bila sinkron admin gagal.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record->update($data);
            $this->adminResult = DesaResource::syncAdmin($record, $this->data);

            return $record;
        });
    }

    protected function afterSave(): void
    {
        // Bila akun admin baru dibuat lewat edit (desa lama yang belum punya admin).
        DesaResource::notifyAdminProvisioned($this->adminResult ?? ['created' => false, 'otp' => null], $this->record);
    }
}
