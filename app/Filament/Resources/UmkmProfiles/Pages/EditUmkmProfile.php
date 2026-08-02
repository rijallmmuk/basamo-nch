<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditUmkmProfile extends EditRecord
{
    use RedirectsToView;

    protected static string $resource = UmkmProfileResource::class;

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! UmkmProfileResource::isSelfService()) {
            return $data;
        }

        // Pemilik boleh menyunting seluruh identitas etalasenya sendiri, KECUALI
        // status tayang, pemilik, dan nagari (dikelola operator). Media (logo/sampul/qr)
        // disimpan terpisah oleh komponen media, jadi tak perlu masuk whitelist ini.
        return Arr::only($data, [
            'nama_usaha', 'deskripsi', 'alamat', 'whatsapp', 'email',
            'jam_operasional', 'tahun_berdiri', 'tautan',
        ]);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
