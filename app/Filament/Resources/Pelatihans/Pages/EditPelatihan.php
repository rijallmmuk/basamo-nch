<?php

namespace App\Filament\Resources\Pelatihans\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Pelatihans\Concerns\SyncsPelatihanSasaran;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use App\Models\TemaPelatihan;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditPelatihan extends EditRecord
{
    use RedirectsToView, SyncsPelatihanSasaran;

    protected static string $resource = PelatihanResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    private ?int $temaSebelumnyaId = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    /**
     * Isi field sasaran/jadwal (form-only) dari pivot. Nagari pemilik tak ada di
     * form → otomatis dipertahankan saat simpan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tema_nama'] = $this->record->temaNama();

        return $this->hydrateSasaran($data);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tema = TemaPelatihan::findOrCreateByNama((string) ($data['tema_nama'] ?? ''));

        $this->temaSebelumnyaId = $this->record->tema_pelatihan_id;
        $data['tema_pelatihan_id'] = $tema->getKey();
        unset($data['tema_nama']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncSasaran();
        $this->bersihkanTemaYatim();
    }

    /**
     * Tema lama yang tidak lagi dipakai pelaksanaan mana pun ikut dibuang saat tema
     * pelatihan diganti. Tanpa ini daftar saran tema menumpuk nama yang tak terpakai
     * dan tak ada satu pun layar untuk merapikannya.
     */
    private function bersihkanTemaYatim(): void
    {
        if ($this->temaSebelumnyaId === null
            || $this->temaSebelumnyaId === $this->record->tema_pelatihan_id) {
            return;
        }

        TemaPelatihan::query()
            ->whereKey($this->temaSebelumnyaId)
            ->tidakDipakai()
            ->delete();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
