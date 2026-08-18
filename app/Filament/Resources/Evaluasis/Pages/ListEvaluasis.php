<?php

namespace App\Filament\Resources\Evaluasis\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

abstract class ListEvaluasis extends ListRecords
{
    use ExportsTableReports;
    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            $this->reportActionGroup(),
            CreateAction::make()->color('primary'),
        ];
    }

    protected function reportTitle(): string
    {
        return static::getResource()::getPluralModelLabel();
    }

    protected function reportFormats(): array { return ['xlsx']; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('module.pelatihan.tema.nama', 'Pelatihan', 30),
            new ReportColumn('module.judul', 'Modul', 32),
            new ReportColumn('pertanyaans_count', 'Jumlah Soal', 14),
            new ReportColumn('nilai_lulus', 'Nilai Lulus', 14),
            new ReportColumn('maks_percobaan', 'Batas Percobaan', 16, fn ($value): string => (int) $value === 0 ? 'Tanpa batas' : (string) $value),
            new ReportColumn('updated_at', 'Diperbarui', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
        ];
    }
}
