<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\Filament\Resources\Beritas\BeritaResource;
use App\Filament\Resources\Beritas\Concerns\SyncsBeritaSasaran;
use App\Filament\Resources\Concerns\RedirectsToView;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditBerita extends EditRecord
{
    use RedirectsToView, SyncsBeritaSasaran;

    protected static string $resource = BeritaResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->hydrateSasaran($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $actor = auth()->user();

        if ($actor?->isOperator()) {
            $data['nagari_id'] = $actor->nagari_id;
            $data['semua_nagari'] = false;
        } else {
            $data['semua_nagari'] = ! empty($data['semua_nagari']);
            if ($data['semua_nagari']) {
                $data['nagari_id'] = null;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncSasaran();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
