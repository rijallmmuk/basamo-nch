<?php

declare(strict_types=1);

namespace App\Support\Reports;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

final class ReportActionGroup
{
    /** @param Closure(): TabularReport $report */
    public static function make(Closure $report): ActionGroup
    {
        return ActionGroup::make([
            Action::make('eksporExcel')
                ->label('Excel (.xlsx)')
                ->icon('heroicon-o-table-cells')
                ->action(fn () => app(ReportExporter::class)->xlsx($report(), auth()->user())),
            Action::make('eksporPdf')
                ->label('PDF (.pdf)')
                ->icon('heroicon-o-document-text')
                ->action(fn () => app(ReportExporter::class)->pdf($report(), auth()->user())),
        ])->label('Ekspor')->icon('heroicon-o-arrow-down-tray')->button()->color('gray');
    }
}
