<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    use ExportsTableReports;
    use HasListTitle;

    // Read-only: tak ada aksi buat.
    protected function getHeaderActions(): array
    {
        return [$this->reportActionGroup()];
    }

    protected function reportTitle(): string { return 'Log Aktivitas Sistem'; }

    protected function reportFormats(): array { return ['xlsx']; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('created_at', 'Waktu', 20, fn ($value): string => $value?->format('d/m/Y H:i:s') ?? '—'),
            new ReportColumn('causer.name', 'Pelaku', 28, fn ($value): string => $value ?: 'Sistem'),
            new ReportColumn('causer', 'Peran Pelaku', 20, fn ($value, $record): string => $record->causer && method_exists($record->causer, 'primaryRole') ? ($record->causer->primaryRole() ?? '—') : 'Sistem'),
            new ReportColumn('log_name', 'Objek', 18),
            new ReportColumn('event', 'Aksi', 16),
            new ReportColumn('subject_id', 'ID Objek', 12),
            new ReportColumn('description', 'Keterangan', 50),
        ];
    }
}
