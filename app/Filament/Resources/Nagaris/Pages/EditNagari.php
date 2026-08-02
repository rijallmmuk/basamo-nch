<?php

namespace App\Filament\Resources\Nagaris\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Nagaris\NagariResource;
use App\Services\NagariManagementService;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditNagari extends EditRecord
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
     * Isi field admin (`admin_*`, tak dehidrasi) dari akun operator yang ada. Username
     * tak diisi (otomatis dari kode).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $admin = $this->record->operator()->first();

        // Username read-only ditampilkan dari admin yang ada, atau diturunkan dari kode.
        $data['operator_username_display'] = $admin?->username ?? $this->record->defaultOperatorUsername();
        $data['operator_email'] = $admin?->email;
        $data['operator_kontak'] = $admin?->phone;

        return $data;
    }

    /**
     * Perbarui nagari + sinkron akun operator dalam satu transaksi (simetris dgn Create)
     * agar tak ada keadaan setengah-jadi bila sinkron operator gagal.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            if (auth()->user()?->isSuperAdmin()) {
                // Kode wilayah tak bisa diubah dari form (disabled saat edit); re-resolve
                // hanya menyegarkan atribut turunan dari data referensi. Non-fatal: bila
                // referensi wilayah hilang, simpan tetap lanjut dengan atribut tersimpan
                // agar sekadar mengganti foto sampul tidak ikut gagal.
                $wilayahKode = (string) ($data['wilayah_kode'] ?? $record->wilayah_kode ?? '');
                $wilayah = app(NagariManagementService::class)->officialWilayahAttributesOrNull($wilayahKode);

                $record->update($wilayah !== null ? array_replace($data, $wilayah) : $data);
            }

            $this->operatorResult = NagariResource::syncOperator($record, $this->data);

            return $record;
        });
    }

    protected function afterSave(): void
    {
        // Bila akun operator baru dibuat lewat edit (nagari lama yang belum punya admin).
        NagariResource::notifyOperatorProvisioned($this->operatorResult ?? ['created' => false], $this->record);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
