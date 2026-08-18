<?php

namespace App\Filament\Resources\BackofficeUsers\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Resources\BackofficeUsers\BackofficeUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

class ListBackofficeUsers extends ListRecords
{
    use ExportsTableReports;

    protected static string $resource = BackofficeUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->reportActionGroup(),
            CreateAction::make()->color('primary'),
        ];
    }

    protected function reportTitle(): string { return 'Daftar Pengguna Back-office'; }

    protected function reportFormats(): array { return ['xlsx']; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('name', 'Nama', 28),
            new ReportColumn('username', 'Username', 22),
            new ReportColumn('roles', 'Peran', 22, fn ($value, $record): string => $record->roles->pluck('name')->join(', ')),
            new ReportColumn('lembaga', 'Lembaga', 28),
            new ReportColumn('email', 'Email', 28),
            new ReportColumn('phone', 'Nomor HP', 20),
            new ReportColumn('status', 'Status', 14, fn ($value): string => $value instanceof \BackedEnum ? $value->value : (string) $value),
            new ReportColumn('created_at', 'Dibuat', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
            new ReportColumn('updated_at', 'Diperbarui', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
        ];
    }
}
