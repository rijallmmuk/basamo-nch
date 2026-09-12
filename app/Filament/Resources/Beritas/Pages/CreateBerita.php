<?php

namespace App\Filament\Resources\Beritas\Pages;

use App\Filament\Resources\Beritas\BeritaResource;
use App\Filament\Resources\Beritas\Concerns\SyncsBeritaSasaran;
use App\Filament\Resources\Concerns\RedirectsToView;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateBerita extends CreateRecord
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
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();
        $data['created_by'] = $actor?->getKey();

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

    protected function afterCreate(): void
    {
        $this->syncSasaran();
    }
}
