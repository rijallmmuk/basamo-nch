<?php

namespace App\Filament\Resources\Evaluasis\Pages;

use App\Models\Evaluasi;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

abstract class ViewEvaluasi extends ViewRecord
{
    public function getTitle(): string
    {
        /** @var Evaluasi $evaluasi */
        $evaluasi = $this->getRecord();

        return $evaluasi->jenis->getLabel().': '.($evaluasi->module?->judul ?? 'Modul');
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah Pengaturan')
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->trashed()
                    && ! $this->record->isPretest()),

            Action::make('previewWarga')
                ->label('Pratinjau Warga')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! $this->record->trashed()
                    && $this->record->module !== null)
                ->url(function (): string {
                    $module = $this->record->module;

                    $route = $this->record->isPretest()
                        ? 'admin.preview.modules.pretest.show'
                        : 'admin.preview.modules.evaluasi.show';

                    return route($route, ['module' => $module->slug]);
                }),

            ActionGroup::make([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
                ->label('Aksi Lainnya')
                ->icon('heroicon-o-squares-2x2')
                ->button()
                ->color('gray'),
        ];
    }
}
