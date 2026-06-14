<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;

    /**
     * nagari_admin tidak melihat field nagari — paksa ke nagarinya sendiri.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isNagariAdmin()) {
            $data['nagari_id'] = $user->nagari_id;
        }

        return $data;
    }
}
