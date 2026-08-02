<?php

namespace App\Filament\Resources\Nagaris\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Nagaris\NagariResource;
use App\Services\NagariManagementService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateNagari extends CreateRecord
{
    use RedirectsToView;

    protected static string $resource = NagariResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    /** @var array{created: bool}|null Hasil provisioning admin. */
    protected ?array $operatorResult = null;

    /**
     * Buat nagari + akun operator nagarinya dalam satu transaksi. Field `admin_*` tidak
     * dehidrasi ke model Nagari; dibaca dari state form mentah (`$this->data`).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data = array_replace(
            $data,
            app(NagariManagementService::class)->officialWilayahAttributes((string) ($data['wilayah_kode'] ?? '')),
        );

        return DB::transaction(function () use ($data): Model {
            $nagari = static::getModel()::create($data);
            $this->operatorResult = NagariResource::syncOperator($nagari, $this->data);

            return $nagari;
        });
    }

    protected function afterCreate(): void
    {
        NagariResource::notifyOperatorProvisioned($this->operatorResult ?? ['created' => false], $this->record);

        // Nagari sudah commit di handleRecordCreation → aman menarik skor eksternal sinkron
        // (best-effort). Aksi Filament menampilkan spinner selama proses ini berjalan.
        NagariResource::fetchSdgsOnCreate($this->record);
        NagariResource::fetchIdmOnCreate($this->record);
    }
}
