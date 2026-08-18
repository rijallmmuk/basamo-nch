<?php

namespace App\Filament\Resources\Pelatihans\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

class ListPelatihans extends ListRecords
{
    protected static string $resource = PelatihanResource::class;

    use ExportsTableReports;
    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            $this->reportActionGroup(),
            CreateAction::make()
                ->label('Tambah Pelatihan')
                ->color('primary'),
        ];
    }

    protected function reportTitle(): string { return 'Daftar Pelatihan'; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('tema.nama', 'Tema Pelatihan', 30),
            new ReportColumn('deskripsi', 'Deskripsi', 42, fn ($value): string => trim(strip_tags((string) $value))),
            new ReportColumn('semua_nagari', 'Cakupan', 28, fn ($value, $record): string => $record->semua_nagari ? 'Semua nagari' : $record->nagaris->pluck('nama')->join(', ')),
            new ReportColumn('creator.name', 'Pembuat', 25),
            new ReportColumn('pengajars', 'Pengajar', 30, fn ($value, $record): string => $record->pengajars->pluck('name')->join(', ')),
            new ReportColumn('modules_count', 'Jumlah Modul', 13),
            new ReportColumn('status', 'Status Akses', 15, fn ($value): string => $value instanceof \BackedEnum ? $value->value : (string) $value),
            new ReportColumn('updated_at', 'Diperbarui', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
        ];
    }
}
