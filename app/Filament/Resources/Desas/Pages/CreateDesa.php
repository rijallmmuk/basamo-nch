<?php

namespace App\Filament\Resources\Desas\Pages;

use App\Filament\Resources\Desas\DesaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateDesa extends CreateRecord
{
    protected static string $resource = DesaResource::class;

    /** OTP admin yang diterbitkan saat membuat desa (ditampilkan setelah simpan). */
    protected ?string $issuedOtp = null;

    /**
     * Buat desa + akun admin desanya dalam satu transaksi. Field `admin_*` tidak
     * dehidrasi ke model Desa; dibaca dari state form mentah (`$this->data`).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $desa = static::getModel()::create($data);
            $this->issuedOtp = DesaResource::syncAdmin($desa, $this->data);

            return $desa;
        });
    }

    /** Setelah simpan, kembali ke daftar desa (bukan halaman edit yang mirip create). */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $admin = $this->record->desaAdmin()->first();

        if ($admin && filled($this->issuedOtp)) {
            Notification::make()
                ->title('Desa & akun admin dibuat')
                ->body("Username: {$admin->username} · OTP: {$this->issuedOtp}. Sampaikan ke admin desa; wajib diganti saat login pertama.")
                ->success()
                ->persistent()
                ->send();
        }
    }
}
