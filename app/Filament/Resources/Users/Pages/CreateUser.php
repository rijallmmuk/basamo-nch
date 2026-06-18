<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * nagari_admin tidak melihat field nagari — akun yang dibuat dipaksa
     * ke nagarinya sendiri (warga/umkm_owner).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if ($actor->isNagariAdmin()) {
            $data['nagari_id'] = $actor->nagari_id;
        }

        // super_admin (global) tidak terikat nagari.
        if (($data['role'] ?? null) === 'super_admin') {
            $data['nagari_id'] = null;
        }

        return $data;
    }
}
