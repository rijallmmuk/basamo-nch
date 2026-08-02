<?php

namespace App\Filament\Resources\Penduduks\Concerns;

use App\Filament\Resources\Nagaris\NagariResource;
use App\Models\Nagari;
use App\Support\NagariContext;

trait BelongsToNagariContext
{
    protected function getNagariContextId(): ?int
    {
        if (property_exists($this, 'record') && $this->record?->nagari_id) {
            return $this->record->nagari_id;
        }

        return request()->integer('nagari_id') ?: auth()->user()?->managedNagariId(NagariContext::WARGA);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getNagariContextId()
            ? NagariResource::getUrl('view', ['record' => $this->getNagariContextId()])
            : NagariResource::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        if ($nagariId = $this->getNagariContextId()) {
            $nagari = Nagari::find($nagariId);
            $nagariUrl = NagariResource::getUrl('view', ['record' => $nagariId]);

            $breadcrumbs = [
                $nagariUrl => $nagari?->nama ?? 'Detail Nagari',
                $this->getBreadcrumb(),
            ];

            if (! (auth()->user()?->isOperator() ?? false)) {
                $breadcrumbs = [
                    NagariResource::getUrl('index') => 'Nagari',
                    ...$breadcrumbs,
                ];
            }

            return $breadcrumbs;
        }

        return parent::getBreadcrumbs();
    }
}
