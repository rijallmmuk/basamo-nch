<?php

namespace App\Filament\Resources\Desas\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Desas\DesaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateDesa extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = DesaResource::class;

    /** @var array{created: bool, otp: ?string}|null Hasil provisioning admin. */
    protected ?array $adminResult = null;

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
            $this->adminResult = DesaResource::syncAdmin($desa, $this->data);

            return $desa;
        });
    }

    protected function afterCreate(): void
    {
        DesaResource::notifyAdminProvisioned($this->adminResult ?? ['created' => false, 'otp' => null], $this->record);
    }
}
