<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Modules\ModuleResource;
use App\Services\SlcModuleService;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditModule extends EditRecord
{
    use RedirectsToView;

    protected static string $resource = ModuleResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(SlcModuleService::class)->authoringData($data, auth()->user(), $this->record);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
