<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;

    /**
     * desa_admin tidak melihat field desa — paksa ke desanya sendiri.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isDesaAdmin()) {
            $data['desa_id'] = $user->desa_id;
        }

        return $data;
    }
}
