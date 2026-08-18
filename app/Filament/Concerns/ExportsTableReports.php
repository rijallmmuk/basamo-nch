<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportExporter;
use App\Support\Reports\TabularReport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

trait ExportsTableReports
{
    abstract protected function reportTitle(): string;

    /** @return list<ReportColumn> */
    abstract protected function reportColumns(): array;

    protected function reportFilename(): string
    {
        return $this->reportTitle();
    }

    /** @return array<string, string> */
    protected function reportMetadata(): array
    {
        return [];
    }

    protected function reportOrientation(): string
    {
        return 'landscape';
    }

    /** @return list<string> */
    protected function reportFormats(): array
    {
        return ['xlsx', 'pdf'];
    }

    protected function reportActionGroup(): ActionGroup
    {
        $actions = [];

        if (in_array('xlsx', $this->reportFormats(), true)) {
            $actions[] = Action::make('eksporExcel')
                ->label('Excel (.xlsx)')
                ->icon('heroicon-o-table-cells')
                ->action(fn () => app(ReportExporter::class)->xlsx($this->makeTabularReport(), auth()->user()));
        }

        if (in_array('pdf', $this->reportFormats(), true)) {
            $actions[] = Action::make('eksporPdf')
                ->label('PDF (.pdf)')
                ->icon('heroicon-o-document-text')
                ->action(fn () => app(ReportExporter::class)->pdf($this->makeTabularReport(), auth()->user()));
        }

        return ActionGroup::make($actions)
            ->label('Ekspor')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->color('gray');
    }

    protected function makeTabularReport(): TabularReport
    {
        return new TabularReport(
            title: $this->reportTitle(),
            filename: $this->reportFilename(),
            query: $this->getTableQueryForExport(),
            columns: $this->reportColumns(),
            metadata: $this->reportMetadata(),
            orientation: $this->reportOrientation(),
        );
    }
}
