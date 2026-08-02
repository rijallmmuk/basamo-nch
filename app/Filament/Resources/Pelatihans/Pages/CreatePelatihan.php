<?php

namespace App\Filament\Resources\Pelatihans\Pages;

use App\Enums\StatusPelatihan;
use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Pelatihans\Concerns\SyncsPelatihanSasaran;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use App\Models\TemaPelatihan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreatePelatihan extends CreateRecord
{
    use RedirectsToView, SyncsPelatihanSasaran;

    protected static string $resource = PelatihanResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        $data['created_by'] = $actor?->getKey();
        $data = $this->resolveTema($data);

        // Ditetapkan sistem: selalu mulai Terkunci. Membukanya lewat aksi tersendiri
        // setelah isinya siap.
        $data['status'] = StatusPelatihan::Terkunci;

        // Audiens ditentukan pivot sasaran; khusus operator dipaksa ke nagarinya.
        $data['nagari_id'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Pembuat TIDAK dicantolkan ke pivot: kepemilikannya berasal dari `created_by`,
        // sedangkan pivot `pelatihan_pengajar` khusus mendaftar kolaborator pembantu.
        $this->syncSasaran();
    }

    /**
     * Ubah isian bebas "Tema Pelatihan" menjadi relasi. Tema yang setara memakai baris
     * yang sudah ada (ejaannya TIDAK ditimpa); yang belum ada langsung dicatat di sini.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveTema(array $data): array
    {
        $nama = (string) ($data['tema_nama'] ?? '');
        $sudahAda = TemaPelatihan::sudahTercatat($nama);
        $tema = TemaPelatihan::findOrCreateByNama($nama);

        if ($sudahAda) {
            Notification::make()
                ->title('Memakai tema yang sudah tercatat')
                ->body('Tema "'.$tema->nama.'" sudah pernah dipakai, jadi pelatihan ini memakainya.')
                ->info()
                ->send();
        }

        $data['tema_pelatihan_id'] = $tema->getKey();
        unset($data['tema_nama']);

        return $data;
    }
}
