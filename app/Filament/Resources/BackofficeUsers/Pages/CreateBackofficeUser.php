<?php

namespace App\Filament\Resources\BackofficeUsers\Pages;

use App\Filament\Resources\BackofficeUsers\BackofficeUserResource;
use App\Filament\Resources\Concerns\RedirectsToView;
use App\Services\BackofficeUserService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBackofficeUser extends CreateRecord
{
    use RedirectsToView;

    protected static string $resource = BackofficeUserResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        // Field peran adalah pilihan tunggal; service tetap menerima array.
        $roles = array_values(array_filter((array) ($data['role_names'] ?? [])));
        unset($data['role_names']);

        return app(BackofficeUserService::class)->create($data, $roles);
    }
}
