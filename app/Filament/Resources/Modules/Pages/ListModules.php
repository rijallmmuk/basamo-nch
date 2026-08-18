<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Modules\ModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    use ExportsTableReports;
    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            $this->reportActionGroup(),
            CreateAction::make()->color('primary'),
        ];
    }

    protected function reportTitle(): string { return 'Daftar Modul Pelatihan'; }

    protected function reportFormats(): array { return ['xlsx']; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('judul', 'Judul Modul', 32),
            new ReportColumn('pelatihan.tema.nama', 'Pelatihan', 30),
            new ReportColumn('creator.name', 'Pembuat', 24),
            new ReportColumn('materis_count', 'Jumlah Materi', 13),
            new ReportColumn('pretest', 'Pre-test', 12, fn ($value): string => $value ? 'Tersedia' : 'Tidak'),
            new ReportColumn('evaluasiKegiatan', 'Evaluasi Kegiatan', 18, fn ($value): string => $value ? 'Tersedia' : 'Tidak'),
            new ReportColumn('prerequisite.judul', 'Prasyarat', 28, fn ($value): string => $value ?: 'Tidak ada'),
            new ReportColumn('updated_at', 'Diperbarui', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
        ];
    }
}
