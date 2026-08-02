<?php

namespace App\Filament\Resources\Penduduks\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Penduduks\Concerns\BelongsToNagariContext;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Services\WargaProvisioningService;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditPenduduk extends EditRecord
{
    use BelongsToNagariContext, RedirectsToView {
        RedirectsToView::getRedirectUrl insteadof BelongsToNagariContext;
    }

    protected static string $resource = PendudukResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, [
            'email' => $this->record->user?->email,
            'phone' => $this->record->user?->phone,
            'status' => $this->record->user?->status?->value,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(WargaProvisioningService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
