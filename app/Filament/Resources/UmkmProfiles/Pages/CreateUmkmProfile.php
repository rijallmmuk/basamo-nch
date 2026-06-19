<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUmkmProfile extends CreateRecord
{
    protected static string $resource = UmkmProfileResource::class;

    /**
     * Nagari mengikuti nagari pemilik — tidak diisi manual.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['nagari_id'] = User::whereKey($data['user_id'])->value('nagari_id');

        return $data;
    }
}
